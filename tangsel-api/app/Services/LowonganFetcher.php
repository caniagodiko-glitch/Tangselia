<?php

namespace App\Services;

use App\Models\Lowongan;
use App\Models\Sumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Pengambil lowongan kerja dari sumber RSS lowongan.
 *
 * Alurnya sama dengan NewsFetcher: unduh feed, dedup berdasarkan URL
 * artikel asli, ambil isi artikel setelah memeriksa robots.txt, ekstrak
 * data terstruktur, lalu klasifikasikan. Bedanya, hasil lowongan disimpan
 * dengan status "baru" dan menunggu kurasi administrator sebelum tayang.
 */
class LowonganFetcher
{
    /**
     * Nama agen pengidentifikasi, konsisten dengan ArticleContentFetcher.
     */
    private const USER_AGENT = 'TANGSEL-LIA-Bot/1.0 (+https://tangselia.id)';

    public function __construct(
        private FeedParser $feedParser,
        private ArticleContentFetcher $contentFetcher,
        private AIEkstraksiLowongan $ekstraktor,
        private LowonganClassifier $classifier,
    ) {}

    /**
     * Ambil seluruh sumber lowongan aktif, atau satu sumber saja.
     *
     * @return array{sumber: int, dibuat: int, dilewati: int, gagal: int}
     */
    public function run(?string $source = null): array
    {
        $stats = ['sumber' => 0, 'dibuat' => 0, 'dilewati' => 0, 'gagal' => 0];

        $query = Sumber::query()->where('aktif', true)->where('tipe', 'lowongan');

        if ($source !== null && $source !== '') {
            $query->where(function ($inner) use ($source) {
                $inner->where('id', $source)
                    ->orWhere('nama', 'like', '%'.$source.'%');
            });
        }

        foreach ($query->get() as $sumber) {
            $stats['sumber']++;

            try {
                $hasil = $this->fetchFromSource($sumber);
                $stats['dibuat'] += $hasil['dibuat'];
                $stats['dilewati'] += $hasil['dilewati'];

                $sumber->update([
                    'last_fetched_at' => now(),
                    'last_status' => 'ok',
                    'last_error' => null,
                    'item_count' => $hasil['item_count'],
                ]);
            } catch (Throwable $e) {
                $stats['gagal']++;
                Log::warning('lowongan:fetch gagal pada sumber "'.$sumber->nama.'": '.$e->getMessage());

                $sumber->update([
                    'last_fetched_at' => now(),
                    'last_status' => 'error',
                    'last_error' => Str::limit($e->getMessage(), 400),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Proses satu sumber: unduh feed, dedup, ekstrak, klasifikasikan.
     *
     * @return array{dibuat: int, dilewati: int, item_count: int}
     */
    private function fetchFromSource(Sumber $sumber): array
    {
        $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout(15)
            ->get($sumber->feed_url);

        if (! $response->successful()) {
            throw new RuntimeException('HTTP '.$response->status().' untuk '.$sumber->feed_url);
        }

        $items = $this->feedParser->parse($response->body());
        $dibuat = 0;
        $dilewati = 0;
        $maks = (int) config('lowongan.maks_item_per_sumber', 20);

        foreach (array_slice($items, 0, $maks) as $item) {
            $urlAsli = $item['link'];

            if ($item['judul'] === '' || $urlAsli === null || $urlAsli === '') {
                $dilewati++;

                continue;
            }

            $urlAsli = mb_substr($urlAsli, 0, 500);

            if (Lowongan::where('url_asli', $urlAsli)->exists()) {
                $dilewati++;

                continue;
            }

            $judul = $this->ekstraktor->rapikanJudul($item['judul'], $item['source_name'] ?? null);
            $judulHash = $this->hashJudul($judul);

            if ($judulHash !== '' && Lowongan::where('judul_hash', $judulHash)->exists()) {
                $dilewati++;

                continue;
            }

            if ($this->create($sumber, $item, $urlAsli, $judulHash) !== null) {
                $dibuat++;
            } else {
                $dilewati++;
            }
        }

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati, 'item_count' => count($items)];
    }

    /**
     * @param  array{judul: string, link: ?string, pub_date: ?Carbon, ringkasan: string, gambar: ?string, source_name: ?string, source_url: ?string}  $item
     */
    private function create(Sumber $sumber, array $item, string $urlAsli, string $judulHash): ?Lowongan
    {
        $sumberNama = $item['source_name'] ?? null;
        $judul = $this->ekstraktor->rapikanJudul($item['judul'], $sumberNama);

        if ($judul === '' || $this->samaDenganSumber($judul, $sumberNama, $sumber->nama)) {
            return null;
        }

        // Cuplikan feed adalah ringkasan yang ditulis penerbit dan
        // jauh lebih bersih daripada teks halaman yang masih memuat
        // menu dan navigasi situs.
        $cuplikan = $this->ringkasan($item['ringkasan'] ?? null);
        $isi = $cuplikan;

        if ((bool) config('lowongan.ambil_isi_artikel', true)) {
            $isiArtikel = $this->contentFetcher->fetch($urlAsli);

            if ($isiArtikel !== null) {
                $isi = $isiArtikel;
            }
        }

        $hasil = $this->ekstraktor->ekstrak($judul, $isi);

        if ($hasil === null) {
            $hasil = $this->ekstraktor->parseDariJudul($judul);
            $hasil['deskripsi'] = $cuplikan;
            $hasil['kualifikasi'] = null;
            $hasil['cara_apply'] = null;
        }

        $posisi = $hasil['posisi'] ?? null;
        $perusahaan = $hasil['perusahaan'] ?? null;
        $deskripsi = $this->bersihkanDeskripsi($hasil['deskripsi'] ?? null, $judul);

        if ($posisi === null && $perusahaan === null) {
            return null;
        }

        $teks = $judul.' '.($posisi ?? '').' '.($perusahaan ?? '').' '.($deskripsi ?? '');
        $klasifikasi = $this->classifier->classify($teks);

        try {
            return Lowongan::create([
                'sumber_id' => $sumber->id,
                'kategori_id' => $klasifikasi['kategori_id'],
                'kecamatan_id' => $klasifikasi['kecamatan_id'],
                'kode' => $this->uniqueKode(),
                'judul' => $judul,
                'slug' => $this->uniqueSlug($judul, $urlAsli),
                'judul_hash' => $judulHash,
                'posisi' => $posisi,
                'perusahaan' => $perusahaan,
                'jenis' => $klasifikasi['jenis'],
                'deskripsi' => $deskripsi,
                'kualifikasi' => $hasil['kualifikasi'] ?? null,
                'cara_apply' => $hasil['cara_apply'] ?? null,
                'url_asli' => $urlAsli,
                'sumber' => $sumberNama ?? $sumber->nama,
                'sumber_url' => $item['source_url'] ?? $this->origin($sumber->feed_url),
                'status' => 'baru',
                'relevan' => $this->relevan($judul, $sumber),
                'catatan_admin' => null,
                'tayang_at' => null,
                'kedaluwarsa_at' => null,
                'published_at' => $item['pub_date'] ?? now(),
                'fetched_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('lowongan:fetch gagal menyimpan "'.$judul.'": '.$e->getMessage());

            return null;
        }
    }

    /**
     * Menentukan apakah judul memang menyebut lowongan kerja.
     *
     * Sumber dari job board selalu relevan karena isinya lowongan utuh.
     * Pencarian umum hanya relevan bila judulnya menyebut lowongan secara
     * eksplisit, sehingga berita umum yang kebetulan memuat kata
     * "loker" tidak langsung dianggap sebagai lowongan.
     */
    private function relevan(string $judul, Sumber $sumber): bool
    {
        $feed = Str::lower(urldecode($sumber->feed_url));

        foreach ((array) config('lowongan.domain_job_board', []) as $domain) {
            if (str_contains($feed, Str::lower((string) $domain))) {
                return true;
            }
        }

        $huruf = Str::lower($judul);

        foreach ((array) config('lowongan.kata_kunci_lowongan', []) as $kata) {
            if (str_contains($huruf, Str::lower((string) $kata))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Judul yang isinya sama dengan nama sumber bukan lowongan.
     */
    private function samaDenganSumber(string $judul, ?string $sumberNama, string $namaSumber): bool
    {
        $judul = Str::lower($judul);

        foreach ([$sumberNama, $namaSumber] as $kandidat) {
            if ($kandidat === null || $kandidat === '') {
                continue;
            }

            if ($judul === Str::lower(trim($kandidat))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Membersihkan teks ringkasan menjadi satu paragraf ringkas.
     */
    private function ringkasan(?string $teks): ?string
    {
        if ($teks === null || trim($teks) === '') {
            return null;
        }

        $teks = (string) preg_replace('/\s+/', ' ', strip_tags($teks));
        $maks = (int) config('lowongan.panjang_deskripsi', 700);
        $teks = trim(mb_substr($teks, 0, $maks));

        return $teks === '' ? null : $teks;
    }

    /**
     * Buang bagian deskripsi yang mengulang judul.
     *
     * Sebagian feed hanya mengulang judul diikuti nama sumber. Teks
     * semacam itu tidak menambah informasi apa pun sehingga tidak
     * disimpan sebagai deskripsi. Sebaliknya, isi artikel yang diawali
     * judul lalu dilanjutkan paragraf isi tetap dipertahankan.
     */
    private function bersihkanDeskripsi(?string $deskripsi, string $judul): ?string
    {
        if ($deskripsi === null) {
            return null;
        }

        $sisa = $this->ambilSisa($deskripsi, $judul);

        if ($sisa !== null) {
            return $sisa === '' ? null : $this->ringkasan($sisa);
        }

        if ($this->samaDengan($deskripsi, $judul)) {
            return null;
        }

        return $this->ringkasan($deskripsi);
    }

    /**
     * Mengembalikan bagian deskripsi yang menyusul judul.
     *
     * Mengembalikan string kosong ketika deskripsi hanya mengulang
     * judul, dan null ketika judul bukan awal dari deskripsi.
     */
    private function ambilSisa(string $deskripsi, string $judul): ?string
    {
        $judulBawah = Str::lower($judul);
        $deskripsiBawah = Str::lower($deskripsi);

        if ($judulBawah === '' || $deskripsiBawah === '') {
            return null;
        }

        if (str_starts_with($deskripsiBawah, $judulBawah)) {
            $sisa = substr($deskripsi, strlen($judul));
        } elseif (str_starts_with($judulBawah, $deskripsiBawah)) {
            $sisa = substr($judul, strlen($deskripsi));
        } else {
            return null;
        }

        $sisa = (string) preg_replace('/^[\s\-\|\x{2013}\x{2014}:,.]+/u', '', $sisa);

        // Sisa yang sangat pendek hanya nama sumber atau keterangan
        // sepele sehingga tidak layak disimpan sebagai deskripsi.
        return mb_strlen($sisa) < 30 ? '' : $sisa;
    }

    private function samaDengan(string $satu, string $dua): bool
    {
        $normalize = fn (string $teks): string => trim((string) preg_replace(
            '/[^a-z0-9]+/i',
            ' ',
            Str::lower($teks)
        ));

        $a = $normalize($satu);
        $b = $normalize($dua);

        return $a !== '' && $a === $b;
    }

    /**
     * Sidik jari judul yang dinormalisasi untuk dedup lintas sumber.
     */
    private function hashJudul(string $judul): string
    {
        $teks = (string) preg_replace('/[^a-z0-9]+/i', ' ', Str::lower($judul));
        $teks = trim((string) preg_replace('/\s+/', ' ', $teks));

        if ($teks === '') {
            return '';
        }

        return sha1(mb_substr($teks, 0, 180));
    }

    private function punyaKunciApi(): bool
    {
        $key = config('ai.key');

        return is_string($key) && $key !== '';
    }

    /**
     * Kode lowongan berurutan, misalnya LGN-000123.
     */
    private function uniqueKode(): string
    {
        $prefix = (string) config('lowongan.kode_prefix', 'LGN');
        $terakhir = Lowongan::where('kode', 'like', $prefix.'-%')
            ->orderByDesc('kode')
            ->value('kode');

        $nomor = $terakhir === null
            ? 1
            : ((int) substr($terakhir, strlen($prefix) + 1)) + 1;

        return $prefix.'-'.str_pad((string) $nomor, 6, '0', STR_PAD_LEFT);
    }

    private function uniqueSlug(string $judul, string $urlAsli): string
    {
        $slug = Str::slug($judul);

        if ($slug === '') {
            $slug = 'lowongan-'.substr(md5($urlAsli), 0, 8);
        }

        $basis = $slug;
        $suffix = substr(md5($urlAsli), 0, 6);
        $counter = 1;

        while (Lowongan::where('slug', $slug)->exists()) {
            $slug = $basis.'-'.$suffix;

            if ($counter > 1) {
                $slug .= '-'.$counter;
            }

            $counter++;
        }

        return $slug;
    }

    private function origin(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'];
    }
}
