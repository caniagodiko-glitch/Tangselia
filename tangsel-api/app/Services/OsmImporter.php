<?php

namespace App\Services;

use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class OsmImporter
{
    /**
     * Mengimpor data tempat dari OpenStreetMap (Overpass API) ke tabel
     * direktori. Entri yang sudah ada (ditandai osm_id) dilewati sehingga
     * deskripsi yang pernah disunting admin tidak pernah tertimpa.
     *
     * @param  string  $fitur  Kunci fitur pada config/osm.php: tempat, olahraga, kesehatan.
     * @param  string|null  $jenis  Batasi impor ke satu nilai jenis saja.
     * @return array{dibuat: int, dilewati: int, gagal: int}
     */
    public function import(string $fitur, ?string $jenis = null): array
    {
        $definisi = $this->definisi($fitur);
        $modelClass = $definisi['model'];

        $kecamatan = Kecamatan::all();

        if ($kecamatan->isEmpty()) {
            throw new RuntimeException('Data kecamatan belum tersedia. Jalankan "php artisan db:seed --class=KecamatanSeeder".');
        }

        $elemen = $this->ambilElemen($fitur, $definisi);

        $stats = ['dibuat' => 0, 'dilewati' => 0, 'gagal' => 0];

        foreach ($elemen as $item) {
            $tags = $item['tags'] ?? [];

            if (! is_array($tags)) {
                continue;
            }

            $nama = trim((string) ($tags['name'] ?? ''));

            if ($nama === '') {
                $stats['dilewati']++;

                continue;
            }

            $aturan = $this->cocokkanJenis($tags, $definisi['tags']);

            if ($aturan === null || ($jenis !== null && $aturan['jenis'] !== $jenis)) {
                $stats['dilewati']++;

                continue;
            }

            $koordinat = $this->koordinat($item);

            if ($koordinat === null) {
                $stats['dilewati']++;

                continue;
            }

            $osmId = $item['type'].'/'.$item['id'];

            if ($modelClass::where('osm_id', $osmId)->exists()) {
                $stats['dilewati']++;

                continue;
            }

            $wilayah = $this->kecamatanTerdekat($koordinat[0], $koordinat[1], $kecamatan);

            if ($wilayah === null) {
                $stats['dilewati']++;

                continue;
            }

            $row = $this->susunData($fitur, $definisi, $tags, $nama, $osmId, $wilayah, $koordinat, $aturan['jenis']);

            try {
                $modelClass::create($row);
                $stats['dibuat']++;
            } catch (Throwable $e) {
                $stats['gagal']++;
                Log::warning('OsmImporter gagal menyimpan "'.$nama.'": '.$e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    private function definisi(string $fitur): array
    {
        $definisi = config('osm.fitur.'.$fitur);

        if (! is_array($definisi)) {
            throw new InvalidArgumentException('Fitur direktori tidak dikenal: '.$fitur);
        }

        return $definisi;
    }

    /**
     * Mengambil elemen OSM untuk seluruh aturan tag pada satu fitur.
     *
     * @param  array<string, mixed>  $definisi
     * @return list<array<string, mixed>>
     */
    private function ambilElemen(string $fitur, array $definisi): array
    {
        $query = $this->bangunQuery($definisi);

        foreach ((array) config('osm.endpoints') as $endpoint) {
            for ($percobaan = 1; $percobaan <= (int) config('osm.percobaan_per_endpoint', 1); $percobaan++) {
                try {
                    $response = Http::withHeaders(['User-Agent' => config('osm.user_agent')])
                        ->asForm()
                        ->timeout((int) config('osm.timeout', 90))
                        ->post($endpoint, ['data' => $query]);

                    if ($response->successful()) {
                        $elemen = $response->json('elements');

                        return is_array($elemen) ? $elemen : [];
                    }

                    Log::warning("OsmImporter {$fitur}: {$endpoint} mengembalikan HTTP {$response->status()} (percobaan {$percobaan}).");
                } catch (Throwable $e) {
                    Log::warning("OsmImporter {$fitur}: {$endpoint} gagal — {$e->getMessage()} (percobaan {$percobaan}).");
                }
            }
        }

        throw new RuntimeException('Seluruh endpoint Overpass gagal dihubungi untuk fitur '.$fitur.'.');
    }

    /**
     * @param  array<string, mixed>  $definisi
     */
    private function bangunQuery(array $definisi): string
    {
        $bbox = implode(',', (array) config('osm.bbox'));
        $baris = [];

        foreach ($definisi['tags'] as $aturan) {
            $filter = '';

            foreach ($aturan['match'] as $kunci => $nilai) {
                $filter .= '["'.$kunci.'"="'.$nilai.'"]';
            }

            $baris[] = '  nwr'.$filter.'('.$bbox.');';
        }

        $timeout = (int) config('osm.timeout', 90);

        return "[out:json][timeout:{$timeout}];\n(\n".implode("\n", $baris)."\n);\nout center tags;";
    }

    /**
     * Menentukan aturan jenis pertama yang cocok dengan tag elemen.
     *
     * @param  array<string, mixed>  $tags
     * @param  list<array{jenis: string, match: array<string, string>}>  $aturan
     * @return array{jenis: string, match: array<string, string>}|null
     */
    private function cocokkanJenis(array $tags, array $aturan): ?array
    {
        foreach ($aturan as $kandidat) {
            $cocok = true;

            foreach ($kandidat['match'] as $kunci => $nilai) {
                if (($tags[$kunci] ?? null) !== $nilai) {
                    $cocok = false;

                    break;
                }
            }

            if ($cocok) {
                return $kandidat;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{0: float, 1: float}|null
     */
    private function koordinat(array $item): ?array
    {
        $lat = $item['lat'] ?? $item['center']['lat'] ?? null;
        $lng = $item['lon'] ?? $item['center']['lon'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }

    /**
     * @param  Collection<int, Kecamatan>  $daftar
     */
    private function kecamatanTerdekat(float $lat, float $lng, Collection $daftar): ?Kecamatan
    {
        $terdekat = null;
        $jarakTerdekat = INF;

        foreach ($daftar as $kecamatan) {
            $pusat = config('osm.kecamatan_centroid.'.$kecamatan->nama);

            if (! is_array($pusat)) {
                continue;
            }

            $jarak = $this->haversine($lat, $lng, (float) $pusat[0], (float) $pusat[1]);

            if ($jarak < $jarakTerdekat) {
                $jarakTerdekat = $jarak;
                $terdekat = $kecamatan;
            }
        }

        return $terdekat;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radius * 2 * asin(min(1.0, sqrt($a)));
    }

    /**
     * @param  array<string, mixed>  $definisi
     * @param  array<string, mixed>  $tags
     * @param  array{0: float, 1: float}  $koordinat
     * @return array<string, mixed>
     */
    private function susunData(
        string $fitur,
        array $definisi,
        array $tags,
        string $nama,
        string $osmId,
        Kecamatan $kecamatan,
        array $koordinat,
        string $jenis,
    ): array {
        $jenis = $this->terapkanOverrideNama($nama, $jenis, $definisi);
        $fasilitas = $this->susunFasilitas($tags);
        $jam = $this->susunJam($tags['opening_hours'] ?? null);

        $row = [
            'kecamatan_id' => $kecamatan->id,
            'jenis' => $jenis,
            'nama' => mb_substr($nama, 0, 255),
            'slug' => $this->slugUnik($definisi['model'], $nama, $osmId),
            'deskripsi' => $this->susunDeskripsi($definisi['label'], $nama, $kecamatan, $tags, $fasilitas, $jam['teks']),
            'alamat' => $this->susunAlamat($tags, $kecamatan),
            'latitude' => $koordinat[0],
            'longitude' => $koordinat[1],
            'fasilitas' => $fasilitas === [] ? null : $fasilitas,
            'status' => 'aktif',
            'sumber' => 'osm',
            'osm_id' => $osmId,
            'google_place_id' => null,
            'jam_buka_teks' => $jam['teks'],
        ];

        if ($fitur === 'tempat' || $fitur === 'kesehatan') {
            $row['jam_buka'] = $jam['buka'];
            $row['jam_tutup'] = $jam['tutup'];
        }

        if ($fitur === 'kesehatan') {
            $row['telepon'] = $this->susunTelepon($tags);
            $row['layanan_24_jam'] = $jam['dua_puluh_empat_jam'];
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $definisi
     */
    private function terapkanOverrideNama(string $nama, string $jenis, array $definisi): string
    {
        $namaKecil = Str::lower($nama);

        foreach ($definisi['override_nama'] ?? [] as $kata => $jenisOverride) {
            if (str_contains($namaKecil, (string) $kata)) {
                return $jenisOverride;
            }
        }

        return $jenis;
    }

    /**
     * @param  array<string, mixed>  $tags
     * @return list<string>
     */
    private function susunFasilitas(array $tags): array
    {
        $fasilitas = [];

        foreach ((array) config('osm.peta_fasilitas') as $kunci => $label) {
            $nilai = $tags[$kunci] ?? null;

            if ($nilai === null || in_array($nilai, ['no', 'none', ''], true)) {
                continue;
            }

            $fasilitas[] = $label;
        }

        return array_values(array_unique($fasilitas));
    }

    /**
     * @return array{buka: string|null, tutup: string|null, teks: string|null, dua_puluh_empat_jam: bool}
     */
    private function susunJam(?string $openingHours): array
    {
        $hasil = ['buka' => null, 'tutup' => null, 'teks' => null, 'dua_puluh_empat_jam' => false];

        if (! is_string($openingHours) || $openingHours === '') {
            return $hasil;
        }

        $hasil['teks'] = mb_substr($openingHours, 0, 255);
        $hasil['dua_puluh_empat_jam'] = str_contains($openingHours, '24/7');

        if (preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $openingHours, $cocok) === 1) {
            $hasil['buka'] = sprintf('%02d:%02d', (int) $cocok[1], (int) $cocok[2]);
            $hasil['tutup'] = sprintf('%02d:%02d', (int) $cocok[3], (int) $cocok[4]);
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $tags
     */
    private function susunTelepon(array $tags): ?string
    {
        $telepon = $tags['phone'] ?? $tags['contact:phone'] ?? null;

        if (! is_string($telepon) || trim($telepon) === '') {
            return null;
        }

        return mb_substr(trim($telepon), 0, 20);
    }

    /**
     * @param  array<string, mixed>  $tags
     */
    private function susunAlamat(array $tags, Kecamatan $kecamatan): string
    {
        $alamat = trim((string) ($tags['addr:full'] ?? ''));

        if ($alamat === '') {
            $jalan = trim((string) ($tags['addr:street'] ?? ''));
            $nomor = trim((string) ($tags['addr:housenumber'] ?? ''));
            $alamat = trim($jalan.' '.$nomor);
        }

        if ($alamat === '') {
            $alamat = 'Kecamatan '.$kecamatan->nama.', '.config('osm.kota');
        }

        return mb_substr($alamat, 0, 255);
    }

    /**
     * Menyusun deskripsi otomatis dari tag OSM (Opsi B). Nilai ini hanya
     * dipakai saat pembuatan; penyuntingan manual admin tidak ditimpa.
     *
     * @param  array<string, mixed>  $tags
     * @param  list<string>  $fasilitas
     */
    private function susunDeskripsi(
        string $label,
        string $nama,
        Kecamatan $kecamatan,
        array $tags,
        array $fasilitas,
        ?string $jamTeks,
    ): string {
        $kalimat = [];

        $kalimat[] = "{$nama} merupakan salah satu {$label} di Kecamatan {$kecamatan->nama}, ".config('osm.kota').'.';

        $alamat = trim((string) ($tags['addr:street'] ?? ''));

        if ($alamat !== '') {
            $kalimat[] = 'Berlokasi di '.$alamat.'.';
        }

        if ($fasilitas !== []) {
            $kalimat[] = 'Fasilitas yang tersedia antara lain '.Str::lower(implode(', ', $fasilitas)).'.';
        }

        if (is_string($jamTeks) && $jamTeks !== '') {
            $kalimat[] = 'Jam operasional: '.$jamTeks.'.';
        }

        $kalimat[] = 'Data ini diambil otomatis dari OpenStreetMap dan dapat diperbarui oleh pengelola.';

        return implode(' ', $kalimat);
    }

    private function slugUnik(string $modelClass, string $nama, string $osmId): string
    {
        $slug = Str::slug($nama);

        if ($slug === '') {
            $slug = 'tempat-'.substr(md5($osmId), 0, 8);
        }

        $basis = $slug;
        $suffix = substr(md5($osmId), 0, 6);
        $counter = 1;

        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = $basis.'-'.$suffix;

            if ($counter > 1) {
                $slug .= '-'.$counter;
            }

            $counter++;
        }

        return $slug;
    }
}
