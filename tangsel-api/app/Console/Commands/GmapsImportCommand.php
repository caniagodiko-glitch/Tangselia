<?php

namespace App\Console\Commands;

use App\Models\FasilitasOlahraga;
use App\Models\Kecamatan;
use App\Models\LayananKesehatan;
use App\Models\Tempat;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

#[Signature(
    'gmaps:import
    {file : Path ke file JSON hasil scraping (gmaps_scraper.py)}
    {--fitur=tempat : Model tujuan: tempat, olahraga, kesehatan}
    {--jenis= : Override jenis tempat (gunakan jika deteksi otomatis kurang tepat)}
    {--kecamatan= : Slug kecamatan default jika tidak terdeteksi dari alamat}
    {--mode=perkaya : perkaya (gabung tanpa duplikat) atau tambah (selalu buat baru)}
    {--radius=150 : Radius (meter) pencocokan nama + lokasi pada mode perkaya}
    {--unduh-foto : Unduh foto pertama ke storage lokal (bila gagal, simpan URL aslinya)}
    {--dry-run : Preview tanpa menyimpan ke database}'
)]
#[Description('Import hasil scraping Google Maps (JSON) ke database direktori — mode perkaya menggabungkan tanpa duplikat')]
class GmapsImportCommand extends Command
{
    /**
     * Peta kata kunci di alamat → slug kecamatan Tangerang Selatan.
     */
    private array $petaKecamatan = [
        'serpong utara' => 'serpong-utara',
        'serpong' => 'serpong',
        'ciputat timur' => 'ciputat-timur',
        'ciputat' => 'ciputat',
        'pamulang' => 'pamulang',
        'pondok aren' => 'pondok-aren',
        'bintaro' => 'pondok-aren',
        'setu' => 'setu',
        'pondok jagung' => 'serpong-utara',
        'gading serpong' => 'serpong',
        'bsd' => 'serpong',
        'lengkong' => 'serpong',
        'rawa buntu' => 'serpong',
        'rawa mekar' => 'serpong',
        'alam sutera' => 'serpong-utara',
        'kelapa dua' => 'serpong-utara',
        'cipondoh' => 'ciputat-timur',
        'jombang' => 'ciputat',
        'ciledug' => 'ciputat-timur',
        'rempoa' => 'ciputat-timur',
        'pondok cabe' => 'pamulang',
        'pondok kacang' => 'pondok-aren',
        'pondok jaya' => 'pondok-aren',
        'pondok maharta' => 'pondok-aren',
        'ciater' => 'serpong',
        'buaran' => 'serpong',
        'babakan' => 'setu',
        'keranggan' => 'setu',
        'kademangan' => 'setu',
        'bakti jaya' => 'setu',
        'pakujaya' => 'serpong-utara',
        'pakulonan' => 'serpong-utara',
        'jelupang' => 'serpong-utara',
    ];

    /**
     * Peta kata kunci kategori GMaps → jenis tempat (kuliner).
     */
    private array $petaJenisKuliner = [
        'kafe' => 'kafe',
        'cafe' => 'kafe',
        'coffee' => 'kafe',
        'kedai kopi' => 'kafe',
        'warkop' => 'warkop',
        'warung kopi' => 'warkop',
        'restoran' => 'restoran',
        'restaurant' => 'restoran',
        'rumah makan' => 'restoran',
        'warteg' => 'restoran',
        'warung' => 'restoran',
        'mie' => 'restoran',
        'bakso' => 'restoran',
        'soto' => 'restoran',
        'seafood' => 'restoran',
        'sunda' => 'restoran',
        'padang' => 'restoran',
        'ayam' => 'restoran',
        'sate' => 'restoran',
        'nasi' => 'restoran',
        'lele' => 'restoran',
        'ikan' => 'restoran',
    ];

    /**
     * Peta kata kunci kategori GMaps → jenis fasilitas olahraga.
     * Nilai harus sesuai enum DB: gor_bulutangkis|lapangan_futsal|kolam_renang|lapangan_basket|gym
     */
    private array $petaJenisOlahraga = [
        'futsal' => 'lapangan_futsal',
        'badminton' => 'gor_bulutangkis',
        'bulutangkis' => 'gor_bulutangkis',
        'bulu tangkis' => 'gor_bulutangkis',
        'gor' => 'gor_bulutangkis',
        'renang' => 'kolam_renang',
        'swimming' => 'kolam_renang',
        'kolam' => 'kolam_renang',
        'basket' => 'lapangan_basket',
        'basketball' => 'lapangan_basket',
        'gym' => 'gym',
        'fitness' => 'gym',
        'kebugaran' => 'gym',
        'yoga' => 'gym',
        'tenis' => 'gor_bulutangkis',
        'voli' => 'lapangan_futsal',
        'climbing' => 'gym',
    ];

    /**
     * Peta kata kunci kategori GMaps → jenis layanan kesehatan.
     * Nilai harus sesuai enum DB: apotek|rumah_sakit|puskesmas|klinik|posyandu
     */
    private array $petaJenisKesehatan = [
        'apotek' => 'apotek',
        'farmasi' => 'apotek',
        'pharmacy' => 'apotek',
        'guardian' => 'apotek',
        'kimia farma' => 'apotek',
        'k24' => 'apotek',
        'rumah sakit' => 'rumah_sakit',
        'hospital' => 'rumah_sakit',
        'rs ' => 'rumah_sakit',
        'rsia' => 'rumah_sakit',
        'rsb' => 'rumah_sakit',
        'puskesmas' => 'puskesmas',
        'pkm' => 'puskesmas',
        'posyandu' => 'posyandu',
        'klinik' => 'klinik',
        'clinic' => 'klinik',
        'dokter' => 'klinik',
        'laboratorium' => 'klinik',
        'lab klinik' => 'klinik',
    ];

    /** @var array<string, bool> google_place_id yang sudah dipakai (hindari bentrok unique). */
    private array $placeIdTerpakai = [];

    /** @var list<array{id: int, lat: float, lng: float}> Titik pusat tiap kecamatan. */
    private array $sentroid = [];

    public function handle(): int
    {
        $filePath = (string) $this->argument('file');
        $fitur = (string) $this->option('fitur');
        $mode = (string) $this->option('mode');
        $radius = (float) $this->option('radius');
        $unduhFoto = (bool) $this->option('unduh-foto');
        $isDry = (bool) $this->option('dry-run');
        $overrideJenis = $this->option('jenis');

        if (! in_array($mode, ['perkaya', 'tambah'], true)) {
            $this->error('Nilai --mode harus "perkaya" atau "tambah".');

            return self::FAILURE;
        }

        // ── Validasi file ─────────────────────────────────────────
        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (! is_array($data) || $data === []) {
            $this->error('File JSON tidak valid atau kosong.');

            return self::FAILURE;
        }

        // ── Tentukan model target ──────────────────────────────────
        $modelClass = match ($fitur) {
            'olahraga' => FasilitasOlahraga::class,
            'kesehatan' => LayananKesehatan::class,
            default => Tempat::class,
        };

        $jenisDefault = match ($fitur) {
            'olahraga' => 'gym',
            'kesehatan' => 'klinik',
            default => 'restoran',
        };

        $this->info(sprintf(
            'Memuat %d entri dari %s (fitur: %s, mode: %s)',
            count($data),
            basename($filePath),
            $fitur,
            $mode,
        ));

        if ($isDry) {
            $this->warn('Mode dry-run: tidak ada yang disimpan ke database.');
        }

        // ── Ambil semua kecamatan sekali ──────────────────────────
        $kecamatanMap = Kecamatan::all()->keyBy('slug');
        $defaultKecSlug = $this->option('kecamatan');

        if ($kecamatanMap->isEmpty()) {
            $this->error('Data kecamatan belum tersedia.');

            return self::FAILURE;
        }

        $this->sentroid = $this->hitungSentroidKecamatan();

        // ── Muat kandidat pencocokan (hanya untuk mode perkaya) ────
        $kandidat = [];

        if ($mode === 'perkaya') {
            $kandidat = $modelClass::query()
                ->get(['id', 'nama', 'latitude', 'longitude'])
                ->map(fn (Model $row): array => [
                    'id' => (int) $row->getKey(),
                    'nama' => $this->normalisasiNama((string) $row->getAttribute('nama')),
                    'lat' => (float) $row->getAttribute('latitude'),
                    'lng' => (float) $row->getAttribute('longitude'),
                ])
                ->all();

            $this->placeIdTerpakai = $modelClass::query()
                ->whereNotNull('google_place_id')
                ->pluck('google_place_id')
                ->filter()
                ->mapWithKeys(fn ($id): array => [(string) $id => true])
                ->all();
        }

        $stats = [
            'dibuat' => 0,
            'diperkaya' => 0,
            'tanpa_perubahan' => 0,
            'dilewati' => 0,
            'gagal' => 0,
        ];
        $jumlahFoto = 0;

        foreach ($data as $i => $item) {
            $no = $i + 1;

            if (! is_array($item)) {
                $stats['dilewati']++;

                continue;
            }

            // ── Validasi field wajib ───────────────────────────────
            $nama = trim((string) ($item['nama'] ?? ''));
            $alamat = trim((string) ($item['alamat'] ?? ''));

            if ($nama === '' || $alamat === '') {
                $this->line("  [{$no}] Dilewati: nama/alamat kosong");
                $stats['dilewati']++;

                continue;
            }

            $lat = isset($item['latitude']) && $item['latitude'] !== '' ? (float) $item['latitude'] : null;
            $lng = isset($item['longitude']) && $item['longitude'] !== '' ? (float) $item['longitude'] : null;

            if ($lat === null || $lng === null) {
                $this->line("  [{$no}] Dilewati: koordinat tidak ada ({$nama})");
                $stats['dilewati']++;

                continue;
            }

            // ── Deteksi kecamatan (dari alamat + nama) ─────────────
            $kecamatanId = $this->deteksiKecamatan($alamat.' '.$nama, $kecamatanMap, $defaultKecSlug, $lat, $lng);

            if (! $kecamatanId) {
                $this->line("  [{$no}] Dilewati: kecamatan tidak terdeteksi ({$nama}) — tambahkan --kecamatan=<slug>");
                $stats['dilewati']++;

                continue;
            }

            // ── Tentukan jenis ─────────────────────────────────────
            $kategori = (string) ($item['kategori'] ?? '');
            $namaLower = mb_strtolower($nama);
            $jenis = $overrideJenis ?? $this->deteksiJenis($fitur, $kategori, $namaLower, $jenisDefault);
            $namaNorm = $this->normalisasiNama($nama);
            $placeId = $this->parseGooglePlaceId($item['google_maps_url'] ?? null);

            // ── Pencocokan (mode perkaya) ──────────────────────────
            $terdaftar = null;

            if ($mode === 'perkaya') {
                $idCocok = $this->cariPasangan($kandidat, $namaNorm, $lat, $lng, $radius);

                if ($idCocok !== null) {
                    $terdaftar = $modelClass::find($idCocok);
                }
            }

            // ── Foto: unduh (opsional) atau simpan URL aslinya ─────
            // Hanya untuk tabel yang punya kolom `gambar`, dan hanya bila
            // baris tujuan memang belum punya foto lokal.
            $urlFoto = is_array($item['foto_url'] ?? null) ? ($item['foto_url'][0] ?? null) : null;
            $punyaFoto = $fitur !== 'kesehatan' && is_string($urlFoto) && $urlFoto !== '';

            $gambar = null;

            if ($punyaFoto) {
                $jumlahFoto++;

                $perluGambar = true;

                if ($terdaftar instanceof Model) {
                    $lama = $terdaftar->getAttribute('gambar');
                    $perluGambar = $this->kosong($lama)
                        || (is_string($lama) && str_starts_with($lama, 'http'));
                }

                if ($perluGambar) {
                    if ($unduhFoto && ! $isDry) {
                        $gambar = $this->unduhFoto($urlFoto, $fitur, $namaNorm);
                    }

                    if ($gambar === null) {
                        $gambar = $urlFoto; // fallback: simpan URL aslinya
                    }
                }
            }

            if ($terdaftar instanceof Model) {
                $diubah = $this->perkayaBaris($terdaftar, $fitur, $item, $gambar, $placeId);

                if ($diubah) {
                    if (! $isDry) {
                        $terdaftar->save();
                    }

                    $this->line("  [{$no}] ↑ diperkaya: {$terdaftar->getAttribute('nama')}");
                    $stats['diperkaya']++;
                } else {
                    $stats['tanpa_perubahan']++;
                }

                continue;
            }

            // ── Buat baru ──────────────────────────────────────────
            $slug = $this->buatSlugUnik($nama, $modelClass);
            $payload = $this->susunPayload($fitur, [
                'kecamatan_id' => $kecamatanId,
                'jenis' => $jenis,
                'nama' => $nama,
                'slug' => $slug,
                'alamat' => $alamat,
                'latitude' => $lat,
                'longitude' => $lng,
                'gambar' => $gambar,
                'placeId' => $placeId,
                'item' => $item,
            ]);

            if ($isDry) {
                $this->line(sprintf(
                    '  [%d] + baru: %s | jenis=%s | kec_id=%d | foto=%s',
                    $no,
                    $nama,
                    $jenis,
                    $kecamatanId,
                    $gambar ? 'ada' : 'tidak',
                ));
                $stats['dibuat']++;

                continue;
            }

            try {
                $baru = $modelClass::create($payload);
                $stats['dibuat']++;

                // Daftarkan agar entri berikutnya bisa mencocokkan baris baru ini.
                $kandidat[] = [
                    'id' => (int) $baru->getKey(),
                    'nama' => $namaNorm,
                    'lat' => $lat,
                    'lng' => $lng,
                ];

                $this->line("  [{$no}] ✓ baru: {$nama} [{$jenis}]");
            } catch (Throwable $e) {
                $this->warn("  [{$no}] ✗ {$nama}: ".$e->getMessage());
                $stats['gagal']++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Selesai — dibuat: %d | diperkaya: %d | tanpa perubahan: %d | dilewati: %d | gagal: %d (foto terdeteksi: %d)',
            $stats['dibuat'],
            $stats['diperkaya'],
            $stats['tanpa_perubahan'],
            $stats['dilewati'],
            $stats['gagal'],
            $jumlahFoto,
        ));

        return self::SUCCESS;
    }

    // ── Helper: Susun payload pembuatan baris baru ─────────────────

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function susunPayload(string $fitur, array $base): array
    {
        $item = $base['item'];
        $placeId = $base['placeId'];
        $gambar = $base['gambar'];

        $telepon = $this->potong($item['telepon'] ?? null, 20);
        $website = $this->potong($item['website'] ?? null, 255);

        // Jam buka dari GMaps sering rusak (encoding) → sengaja tidak dipakai.
        $payload = [
            'kecamatan_id' => $base['kecamatan_id'],
            'jenis' => $base['jenis'],
            'nama' => $this->potong((string) $base['nama'], 255),
            'slug' => $base['slug'],
            'sumber' => 'manual',
            'alamat' => $this->potong((string) $base['alamat'], 255),
            'latitude' => $base['latitude'],
            'longitude' => $base['longitude'],
            'deskripsi' => $this->deskripsiContoh((string) $base['nama'], $fitur, $base['jenis'], (string) $base['alamat']),
            'deskripsi_sumber' => 'gmaps',
            'status' => 'aktif',
            'info_diunduh_pada' => now(),
            'atribusi' => [[
                'jenis' => 'data',
                'sumber' => 'Google Maps',
                'tautan' => $this->potong($item['google_maps_url'] ?? null, 255),
                'peran' => 'rating, telepon, website, foto',
            ]],
        ];

        if ($placeId !== null && ! isset($this->placeIdTerpakai[$placeId])) {
            $payload['google_place_id'] = $placeId;
            $this->placeIdTerpakai[$placeId] = true;
        }

        match ($fitur) {
            'olahraga' => $payload += [
                'gambar' => $gambar,
                'rating' => (float) ($item['rating'] ?? 0),
                'telepon' => $telepon,
                'website' => $website,
            ],
            'kesehatan' => $payload += [
                'telepon' => $telepon,
                'website' => $website,
            ],
            default => $payload += [
                'gambar' => $gambar,
                'telepon' => $telepon,
                'website' => $website,
                'rating' => (float) ($item['rating'] ?? 0),
                'rating_kount' => (int) ($item['rating_kount'] ?? 0),
                'rentang_harga' => $this->potong($item['rentang_harga'] ?? null, 32),
            ],
        };

        return $payload;
    }

    // ── Helper: Perkaya baris yang sudah ada (isi kolom kosong saja) ──

    /**
     * @param  array<string, mixed>  $item
     */
    private function perkayaBaris(Model $row, string $fitur, array $item, ?string $gambar, ?string $placeId): bool
    {
        $diubah = false;

        // Kolom teks yang boleh diisi bila masih kosong (nama kolom → panjang maksimum).
        $teks = [
            'telepon' => [$item['telepon'] ?? null, 20],
            'website' => [$item['website'] ?? null, 255],
            'alamat' => [trim((string) ($item['alamat'] ?? '')) ?: null, 255],
        ];

        if ($fitur === 'tempat') {
            $teks['rentang_harga'] = [$item['rentang_harga'] ?? null, 32];
        }

        foreach ($teks as $kolom => [$nilai, $maks]) {
            if ($this->kosong($row->getAttribute($kolom)) && ! $this->kosong($nilai)) {
                $row->setAttribute($kolom, $this->potong(is_string($nilai) ? $nilai : null, $maks) ?? $nilai);
                $diubah = true;
            }
        }

        // Rating & jumlah ulasan (tempat & olahraga).
        if ($fitur !== 'kesehatan') {
            $rating = (float) ($item['rating'] ?? 0);

            if ($rating > 0 && (float) $row->getAttribute('rating') <= 0) {
                $row->setAttribute('rating', $rating);
                $diubah = true;
            }
        }

        if ($fitur === 'tempat') {
            $kount = (int) ($item['rating_kount'] ?? 0);

            if ($kount > 0 && (int) $row->getAttribute('rating_kount') <= 0) {
                $row->setAttribute('rating_kount', $kount);
                $diubah = true;
            }
        }

        // Foto utama (tempat & olahraga). Isi bila kosong, atau ganti URL jarak
        // jauh dengan berkas lokal yang baru diunduh.
        if ($fitur !== 'kesehatan' && ! $this->kosong($gambar)) {
            $lama = $row->getAttribute('gambar');
            $gambarLokal = is_string($gambar) && ! str_starts_with($gambar, 'http');
            $ganti = $this->kosong($lama)
                || ($gambarLokal && is_string($lama) && str_starts_with($lama, 'http'));

            if ($ganti) {
                $row->setAttribute('gambar', $gambar);
                $diubah = true;
            }
        }

        // google_place_id (bila belum ada dan belum dipakai baris lain).
        if ($placeId !== null && $this->kosong($row->getAttribute('google_place_id')) && ! isset($this->placeIdTerpakai[$placeId])) {
            $row->setAttribute('google_place_id', $placeId);
            $this->placeIdTerpakai[$placeId] = true;
            $diubah = true;
        }

        // Catat atribusi Google Maps bila baris ini benar-benar diperkaya.
        if ($diubah) {
            $atribusi = is_array($row->getAttribute('atribusi')) ? $row->getAttribute('atribusi') : [];
            $sudahAda = false;

            foreach ($atribusi as $entri) {
                if (is_array($entri) && ($entri['sumber'] ?? null) === 'Google Maps') {
                    $sudahAda = true;

                    break;
                }
            }

            if (! $sudahAda) {
                $atribusi[] = [
                    'jenis' => 'data',
                    'sumber' => 'Google Maps',
                    'tautan' => $item['google_maps_url'] ?? null,
                    'peran' => 'rating, telepon, website, foto',
                ];
                $row->setAttribute('atribusi', $atribusi);
            }
        }

        return $diubah;
    }

    // ── Helper: Deskripsi ringkas untuk baris baru ─────────────────

    private function deskripsiContoh(string $nama, string $fitur, string $jenis, string $alamat): ?string
    {
        $label = match ($fitur) {
            'olahraga' => 'fasilitas olahraga',
            'kesehatan' => 'layanan kesehatan',
            default => 'tempat kuliner',
        };

        $jenisRapi = str_replace('_', ' ', $jenis);

        return sprintf(
            '%s adalah %s (%s) yang berlokasi di Kota Tangerang Selatan. Alamatnya di %s.',
            $nama,
            $label,
            $jenisRapi,
            $alamat,
        );
    }

    // ── Helper: Pencocokan nama + lokasi ───────────────────────────

    /**
     * Cari baris yang paling mungkin merupakan tempat yang sama.
     *
     * Skor: 3 = nama identik, 2 = salah satu nama memuat yang lain (seimbang),
     * 1 = berjarak sangat dekat (<= 30 m) dan berbagi satu kata signifikan.
     * Semua kandidat wajib berada dalam $radius meter.
     *
     * @param  list<array{id: int, nama: string, lat: float, lng: float}>  $kandidat
     */
    private function cariPasangan(array $kandidat, string $namaNorm, ?float $lat, ?float $lng, float $radius): ?int
    {
        if ($namaNorm === '') {
            return null;
        }

        $terbaik = null;
        $skorTerbaik = 0;

        foreach ($kandidat as $k) {
            if ($k['nama'] === '') {
                continue;
            }

            $jarak = ($lat !== null && $lng !== null)
                ? $this->jarakMeter($lat, $lng, $k['lat'], $k['lng'])
                : 0.0;

            if ($jarak > $radius) {
                continue;
            }

            $skor = $this->skorNama($namaNorm, $k['nama'], $jarak);

            if ($skor > $skorTerbaik) {
                $skorTerbaik = $skor;
                $terbaik = $k['id'];
            }
        }

        return $skorTerbaik >= 1 ? $terbaik : null;
    }

    /**
     * Skor kemiripan dua nama (0 = tidak mirip).
     */
    private function skorNama(string $a, string $b, float $jarak): int
    {
        if ($a === $b) {
            return 3;
        }

        $min = min(mb_strlen($a), mb_strlen($b));
        $max = max(mb_strlen($a), mb_strlen($b));

        if ($min >= 5 && $max > 0 && $min / $max >= 0.6 && (str_contains($a, $b) || str_contains($b, $a))) {
            return 2;
        }

        if ($jarak <= 30 && $this->berbagiKataSignifikan($a, $b)) {
            return 1;
        }

        return 0;
    }

    /**
     * Apakah dua nama berbagi minimal satu kata bermakna (bukan kata umum).
     */
    private function berbagiKataSignifikan(string $a, string $b): bool
    {
        $umum = [
            'warung', 'nasi', 'kopi', 'cafe', 'kafe', 'coffee', 'resto', 'restoran',
            'rumah', 'makan', 'warteg', 'dan', 'and', 'the', 'group', 'cabang',
            'spbu', 'toko', 'kedai', 'food', 'house', 'seafood', 'bakso', 'sate',
        ];

        $kataA = array_diff(explode(' ', $a), $umum);
        $kataB = array_diff(explode(' ', $b), $umum);

        foreach ($kataA as $kata) {
            if ($kata !== '' && in_array($kata, $kataB, true)) {
                return true;
            }
        }

        return false;
    }

    private function normalisasiNama(string $nama): string
    {
        $nama = mb_strtolower($nama);
        $nama = str_replace(['&', '/', '-'], ' ', $nama);
        $nama = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $nama) ?? '';
        $stop = ['pt', 'cv', 'ud', 'tbk', 'the'];

        $kata = array_filter(
            explode(' ', $nama),
            fn (string $k): bool => $k !== '' && ! in_array($k, $stop, true),
        );

        return trim(implode(' ', $kata));
    }

    private function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // ── Helper: Ekstrak id Google dari URL ────────────────────────

    private function parseGooglePlaceId(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (preg_match('/!1s(0x[0-9a-f]+:0x[0-9a-f]+)/i', $url, $m) === 1) {
            return $m[1];
        }

        if (preg_match('/!16s(?:%2F|\/)([a-z0-9_]+)/i', $url, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    // ── Helper: Unduh foto ke storage lokal ───────────────────────

    private function unduhFoto(string $url, string $fitur, string $namaNorm): ?string
    {
        try {
            $respons = Http::timeout(25)
                ->withOptions(['verify' => false])
                ->get($url);

            if (! $respons->successful()) {
                return null;
            }

            $isi = $respons->body();

            if (strlen($isi) < 1024) {
                return null;
            }

            $tipe = (string) $respons->header('Content-Type');
            $ext = str_contains($tipe, 'png') ? 'png' : 'jpg';
            $slug = Str::slug($namaNorm) ?: 'tempat';
            $path = sprintf('direktori/%s/%s-%s.%s', $fitur, $slug, substr(md5($url), 0, 8), $ext);

            Storage::disk('public')->put($path, $isi);

            return $path;
        } catch (Throwable) {
            return null;
        }
    }

    // ── Helper: Deteksi kecamatan ─────────────────────────────────

    /**
     * Deteksi kecamatan berdasarkan (1) kata kunci pada alamat/nama, lalu
     * (2) default yang diminta pemanggil, lalu (3) kecamatan terdekat dari
     * titik pusat tiap kecamatan (dihitung dari data direktori yang ada).
     *
     * @param  Collection<string, Kecamatan>  $kecamatanMap
     */
    private function deteksiKecamatan(
        string $teks,
        Collection $kecamatanMap,
        ?string $defaultSlug,
        ?float $lat = null,
        ?float $lng = null
    ): ?int {
        $teksLower = mb_strtolower($teks);

        foreach ($this->petaKecamatan as $kata => $slug) {
            if (str_contains($teksLower, $kata)) {
                $kec = $kecamatanMap->get($slug);

                if ($kec) {
                    return $kec->id;
                }
            }
        }

        if ($defaultSlug) {
            $kec = $kecamatanMap->get($defaultSlug);

            if ($kec) {
                return $kec->id;
            }
        }

        if ($lat !== null && $lng !== null && $this->sentroid !== []) {
            $terdekat = null;
            $jarakMin = INF;

            foreach ($this->sentroid as $sentroid) {
                $jarak = $this->jarakMeter($lat, $lng, $sentroid['lat'], $sentroid['lng']);

                if ($jarak < $jarakMin) {
                    $jarakMin = $jarak;
                    $terdekat = $sentroid['id'];
                }
            }

            return $terdekat;
        }

        return null;
    }

    /**
     * Hitung titik pusat (sentroid) tiap kecamatan dari seluruh baris direktori.
     *
     * @return array<int, array{id: int, lat: float, lng: float}>
     */
    private function hitungSentroidKecamatan(): array
    {
        $akumulasi = [];

        foreach ([Tempat::class, FasilitasOlahraga::class, LayananKesehatan::class] as $modelClass) {
            $baris = $modelClass::query()
                ->whereNotNull('kecamatan_id')
                ->selectRaw('kecamatan_id, AVG(latitude) as lat, AVG(longitude) as lng, COUNT(*) as jumlah')
                ->groupBy('kecamatan_id')
                ->get();

            foreach ($baris as $row) {
                $id = (int) $row->kecamatan_id;
                $jumlah = (int) $row->jumlah;

                if ($jumlah <= 0) {
                    continue;
                }

                $akumulasi[$id] ??= ['lat' => 0.0, 'lng' => 0.0, 'jumlah' => 0];
                $akumulasi[$id]['lat'] += (float) $row->lat * $jumlah;
                $akumulasi[$id]['lng'] += (float) $row->lng * $jumlah;
                $akumulasi[$id]['jumlah'] += $jumlah;
            }
        }

        $sentroid = [];

        foreach ($akumulasi as $id => $s) {
            if ($s['jumlah'] > 0) {
                $sentroid[] = [
                    'id' => $id,
                    'lat' => $s['lat'] / $s['jumlah'],
                    'lng' => $s['lng'] / $s['jumlah'],
                ];
            }
        }

        return $sentroid;
    }

    // ── Helper: Deteksi jenis berdasarkan fitur ────────────────────

    private function deteksiJenis(
        string $fitur,
        string $kategori,
        string $namaLower,
        string $jenisDefault
    ): string {
        $peta = match ($fitur) {
            'olahraga' => $this->petaJenisOlahraga,
            'kesehatan' => $this->petaJenisKesehatan,
            default => $this->petaJenisKuliner,
        };

        $teks = mb_strtolower($kategori.' '.$namaLower);

        foreach ($peta as $kata => $jenis) {
            if (str_contains($teks, $kata)) {
                return $jenis;
            }
        }

        return $jenisDefault;
    }

    // ── Helper: Buat slug unik ─────────────────────────────────────

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function buatSlugUnik(string $nama, string $modelClass): string
    {
        $base = Str::slug($nama) ?: 'tempat';
        $slug = $base;
        $i = 1;

        while ($modelClass::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function kosong(mixed $nilai): bool
    {
        return $nilai === null || $nilai === '' || (is_array($nilai) && $nilai === []);
    }

    /**
     * Potong teks agar tidak melebihi panjang kolom database.
     */
    private function potong(?string $nilai, int $maks): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim($nilai);

        return $nilai === '' ? null : mb_substr($nilai, 0, $maks);
    }
}
