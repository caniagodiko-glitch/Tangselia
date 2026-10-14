<?php

namespace App\Services;

use App\Models\Kecamatan;
use App\Models\LayananKesehatan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Pelengkap data rumah sakit dari Wikipedia bahasa Indonesia (CC BY-SA 4.0).
 *
 * Hanya memakai daftar resmi "Daftar rumah sakit di Kota Tangerang Selatan"
 * dan paragraf pembuka artikel masing-masing rumah sakit. Bukan scraping
 * berlebihan: beberapa permintaan per jadwal, atribusi selalu tercantum.
 */
class WikipediaFetcher
{
    private const API = 'https://id.wikipedia.org/w/api.php';

    private const DAFTAR_RS = 'Daftar rumah sakit di Kota Tangerang Selatan';

    /**
     * Kata yang diabaikan saat mencocokkan nama rumah sakit.
     *
     * @var list<string>
     */
    private const KATA_DIBUANG = [
        'rumah', 'sakit', 'rs', 'rsia', 'rsud', 'rsb', 'umum', 'jiwa', 'tht',
        'gigi', 'mulut', 'khusus', 'puskesmas', 'klinik', 'pusat', 'dan', 'atau',
    ];

    /**
     * @return array{baris_dibaca: int, dicocokkan: int, alamat_terisi: int, deskripsi_terisi: int}
     */
    public function sinkronkanRumahSakit(): array
    {
        $wikiteks = $this->ambilWikiteks(self::DAFTAR_RS);

        if ($wikiteks === null) {
            throw new RuntimeException('Wikiteks daftar rumah sakit tidak dapat diambil dari Wikipedia.');
        }

        $entri = $this->parseDaftar($wikiteks);
        $model = LayananKesehatan::with('kecamatan:id,nama')
            ->where('jenis', 'rumah_sakit')
            ->get();
        $kecamatan = Kecamatan::all('id', 'nama');

        $stats = ['baris_dibaca' => count($entri), 'dicocokkan' => 0, 'alamat_terisi' => 0, 'deskripsi_terisi' => 0];

        /** @var array<string, LayananKesehatan> $butuhDeskripsi */
        $butuhDeskripsi = [];

        foreach ($entri as $baris) {
            $cocok = $this->cocokkan($model, $baris, $kecamatan);

            if ($cocok === []) {
                continue;
            }

            $stats['dicocokkan']++;

            foreach ($cocok as $tempat) {
                if (! $this->alamatSebagian($tempat)) {
                    continue;
                }

                $tempat->alamat = mb_substr($baris['alamat'], 0, 255);
                $tempat->atribusi = $this->ejaAtribusi($tempat->atribusi, $this->atribusiAlamat($baris['judul']));
                $tempat->save();

                $stats['alamat_terisi']++;
            }

            if ($baris['judul'] !== null) {
                foreach ($cocok as $tempat) {
                    if (($tempat->deskripsi_sumber ?? 'osm') === 'osm') {
                        $butuhDeskripsi[$baris['judul']] = $tempat;
                    }
                }
            }
        }

        $stats['deskripsi_terisi'] = $this->isikanDeskripsi($butuhDeskripsi);

        return $stats;
    }

    private function ambilWikiteks(string $judul): ?string
    {
        try {
            $respons = Http::timeout(30)->get(self::API, [
                'action' => 'parse',
                'page' => $judul,
                'prop' => 'wikitext',
                'format' => 'json',
                'redirects' => 1,
            ]);
        } catch (Throwable $e) {
            Log::warning('WikipediaFetcher: halaman "'.$judul.'" tidak terjangkau — '.$e->getMessage());

            return null;
        }

        if (! $respons->successful()) {
            Log::warning('WikipediaFetcher: halaman "'.$judul.'" mengembalikan HTTP '.$respons->status().'.');

            return null;
        }

        $wikiteks = $respons->json('parse.wikitext');

        return is_array($wikiteks) && is_string($wikiteks['*'] ?? null) ? $wikiteks['*'] : null;
    }

    /**
     * @return list<array{nama: string, judul: string|null, alamat: string}>
     */
    private function parseDaftar(string $wikiteks): array
    {
        $hasil = [];
        $blok = preg_split('/^\|-\s*$/m', $wikiteks);

        foreach ($blok as $bagian) {
            $sel = $this->barisSel($bagian);

            if ($sel === null) {
                continue;
            }

            $nama = $this->ekstrakNama($sel[2]);

            $hasil[] = [
                'nama' => $nama['label'],
                'judul' => $nama['judul'],
                'alamat' => $this->bersihkan($sel[5] ?? ''),
            ];
        }

        return $hasil;
    }

    /**
     * Memisahkan baris-baris sel tabel dan membersihkannya.
     */
    private function barisSel(string $bagian): ?array
    {
        $garis = preg_split('/\R/', trim($bagian));
        $sel = [];

        foreach ($garis as $baris) {
            $baris = trim($baris);

            if ($baris === '' || str_starts_with($baris, '!') || str_starts_with($baris, '{')) {
                continue;
            }

            if (str_starts_with($baris, '|')) {
                $sel[] = mb_substr($baris, 1);
            }
        }

        if (count($sel) < 6 || preg_match('/^\d/', trim($sel[0] ?? '')) !== 1) {
            return null;
        }

        return $sel;
    }

    /**
     * @return array{label: string, judul: string|null}
     */
    private function ekstrakNama(string $sel): array
    {
        $judul = null;

        if (preg_match('/\[\[([^\]|]+)(?:\|[^\]]+)?\]\]/', $sel, $cocok) === 1) {
            $judul = trim($cocok[1]);
        }

        return ['label' => $this->bersihkan($sel), 'judul' => $judul];
    }

    private function bersihkan(string $teks): string
    {
        $teks = preg_replace('/<ref[^>]*>.*?<\/ref>|<ref[^>]*\/>/s', '', $teks);
        $teks = preg_replace('/\[\[([^\]|]+)\|([^\]]+)\]\]/s', '$2', $teks);
        $teks = preg_replace('/\[\[([^\]]+)\]\]/s', '$1', $teks);
        $teks = preg_replace('/\{\{[^{}]*\}\}/', '', $teks);
        $teks = str_replace(["'''", "''"], '', $teks);
        $teks = preg_replace('/№/u', ' ', $teks);
        $teks = preg_replace('/\s+/', ' ', trim($teks));

        return trim($teks);
    }

    /**
     * @param  Collection<int, LayananKesehatan>  $semua
     * @param  array{nama: string, judul: string|null, alamat: string}  $baris
     * @param  Collection<int, Kecamatan>  $kecamatan
     * @return list<LayananKesehatan>
     */
    private function cocokkan(Collection $semua, array $baris, Collection $kecamatan): array
    {
        $targetTokens = $this->tokensSignifikan($baris['nama']);
        $petunjuk = $this->petunjukKecamatan($baris['alamat'], $kecamatan);

        $skor = [];

        foreach ($semua as $tempat) {
            if ($this->normalisasi($baris['nama']) === $this->normalisasi((string) $tempat->nama)) {
                $skor[$tempat->id] = 1000;

                continue;
            }

            $tokens = $this->tokensSignifikan((string) $tempat->nama);
            $irisan = array_intersect($targetTokens, $tokens);

            if ($irisan === []) {
                continue;
            }

            $nilai = count($irisan) * 10;

            if (array_diff($targetTokens, $tokens) === []) {
                $nilai += 5;
            }

            if (in_array(mb_strtolower((string) $tempat->kecamatan->nama), $petunjuk, true)) {
                $nilai += 80;
            }

            $skor[$tempat->id] = $nilai;
        }

        if ($skor === []) {
            return [];
        }

        $tertinggi = max($skor);

        if ($tertinggi < 20) {
            return [];
        }

        $pemenang = array_keys($skor, $tertinggi, true);

        if ($tertinggi !== 1000 && count($pemenang) > 1) {
            return [];
        }

        return $semua->whereIn('id', $pemenang)->values()->all();
    }

    /**
     * @param  Collection<int, Kecamatan>  $kecamatan
     * @return list<string>
     */
    private function petunjukKecamatan(string $alamat, Collection $kecamatan): array
    {
        $alamat = mb_strtolower($alamat);
        $ditemukan = [];

        foreach ($kecamatan as $entry) {
            $nama = mb_strtolower((string) $entry->nama);

            if ($nama !== '' && str_contains($alamat, $nama)) {
                $ditemukan[] = $nama;
            }
        }

        usort($ditemukan, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        $hanya = [];

        foreach ($ditemukan as $nama) {
            if (! $this->mengandungHanya($hanya, $nama)) {
                $hanya[] = $nama;
            }
        }

        return $hanya;
    }

    /**
     * @param  list<string>  $daftar
     */
    private function mengandungHanya(array $daftar, string $kata): bool
    {
        foreach ($daftar as $lain) {
            if (str_contains($kata, $lain) || str_contains($lain, $kata)) {
                return true;
            }
        }

        return false;
    }

    private function normalisasi(string $teks): string
    {
        $teks = mb_strtolower($teks);

        return trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9\s]+/u', ' ', $teks)));
    }

    /**
     * @return list<string>
     */
    private function tokensSignifikan(string $teks): array
    {
        $kata = preg_split('/\s+/', $this->normalisasi($teks), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($kata, fn (string $k) => ! in_array($k, self::KATA_DIBUANG, true)));
    }

    private function alamatSebagian(LayananKesehatan $tempat): bool
    {
        $alamat = (string) $tempat->alamat;

        return $alamat === '' || str_starts_with($alamat, 'Kecamatan ');
    }

    /**
     * @param  list<array<string, mixed>>|null  $atribusi
     * @return list<array<string, mixed>>
     */
    private function ejaAtribusi(?array $atribusi, array $baru): array
    {
        if ($atribusi === null || $atribusi === []) {
            $atribusi = [];
        }

        foreach ($atribusi as $entri) {
            if (($entri['jenis'] ?? null) === $baru['jenis'] && ($entri['sumber'] ?? null) === $baru['sumber']) {
                return $atribusi;
            }
        }

        return [...$atribusi, $baru];
    }

    /**
     * @return array{jenis: string, sumber: string, lisensi: string, tautan: string, peran: string}
     */
    private function atribusiAlamat(?string $judul): array
    {
        return [
            'jenis' => 'alamat',
            'sumber' => 'Wikipedia bahasa Indonesia',
            'lisensi' => 'CC BY-SA 4.0',
            'tautan' => $this->tautanArtikel($judul),
            'peran' => 'alamat resmi terdaftar',
        ];
    }

    private function tautanArtikel(?string $judul): string
    {
        return 'https://id.wikipedia.org/wiki/'.($judul !== null ? str_replace(' ', '_', $judul) : 'Wikipedia:Lisensi_dokumentasi_bebas');
    }

    /**
     * @param  array<string, LayananKesehatan>  $butuh
     */
    private function isikanDeskripsi(array $butuh): int
    {
        $terisi = 0;

        foreach (array_chunk($butuh, 50, true) as $bagian) {
            $teksPerJudul = $this->ambilIntro(array_keys($bagian));

            foreach ($teksPerJudul as $judul => $teks) {
                $model = $bagian[$judul] ?? null;

                if ($model === null || $teks === null || mb_strlen($teks) < 40) {
                    continue;
                }

                $model->deskripsi = $teks;
                $model->deskripsi_sumber = 'wikipedia';
                $model->atribusi = $this->ejaAtribusi($model->atribusi, [
                    'jenis' => 'deskripsi',
                    'sumber' => 'Wikipedia bahasa Indonesia',
                    'lisensi' => 'CC BY-SA 4.0',
                    'tautan' => $this->tautanArtikel($judul),
                    'peran' => 'paragraf pembuka artikel',
                ]);
                $model->save();

                $terisi++;
            }
        }

        return $terisi;
    }

    /**
     * Mengambil paragraf pembuka (intro) dari sekumpulan artikel sekaligus.
     *
     * @param  list<string>  $judul
     * @return array<string, string|null>
     */
    private function ambilIntro(array $judul): array
    {
        try {
            $respons = Http::timeout(30)->get(self::API, [
                'action' => 'query',
                'prop' => 'extracts',
                'exintro' => 1,
                'explaintext' => 1,
                'redirects' => 1,
                'format' => 'json',
                'titles' => implode('|', $judul),
            ]);
        } catch (Throwable $e) {
            Log::warning('WikipediaFetcher: intro artikel gagal diambil — '.$e->getMessage());

            return [];
        }

        $hasil = [];
        $halaman = is_array($respons->json('query.pages')) ? $respons->json('query.pages') : [];
        $arah = [];

        foreach ((array) $respons->json('query.redirects') as $alan) {
            $arah[(string) ($alan['to'] ?? '')] = (string) ($alan['from'] ?? '');
        }

        foreach ($halaman as $info) {
            $judulAkhir = (string) ($info['title'] ?? '');
            $asli = $arah[$judulAkhir] ?? $judulAkhir;

            if (in_array($asli, $judul, true)) {
                $hasil[$asli] = trim(mb_substr((string) ($info['extract'] ?? ''), 0, 1200));
            }
        }

        return $hasil;
    }
}
