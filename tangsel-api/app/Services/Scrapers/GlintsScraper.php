<?php

namespace App\Services\Scrapers;

use App\Models\Lowongan;
use App\Models\Sumber;
use App\Services\RobotsTxt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Pengambil lowongan dari halaman lokasi Glints.
 *
 * Scraper ini tidak diaktifkan secara bawaan karena halaman Glints
 * dilindungi Cloudflare challenge yang menolak klien HTTP PHP. Sumber
 * Glints di produksi memakai Google News RSS. Kode ini disimpan sebagai
 * opsi bila suatu saat akses langsung diizinkan kembali.
 *
 * Halaman lokasi dipilih karena merupakan halaman publik yang diizinkan
 * robots.txt dan sudah dibatasi pada Tangerang Selatan. Halaman pencarian
 * explore yang memakai query parameter justru dilarang robots.txt sehingga
 * tidak dipakai. Data diambil dari JSON __NEXT_DATA__ milik halaman supaya
 * tidak bergantung pada kelas CSS yang mudah berubah.
 */
class GlintsScraper
{
    public function __construct(private RobotsTxt $robots) {}

    /**
     * Ambil daftar lowongan Glints untuk Tangerang Selatan.
     *
     * @return array{
     *     items: list<array{
     *         judul: string,
     *         url: string,
     *         perusahaan: ?string,
     *         kecamatan: ?string,
     *         lokasi: ?string,
     *         jenis: string,
     *         deskripsi: ?string,
     *         kualifikasi: ?string,
     *         cara_apply: ?string,
     *         sumber: string,
     *         sumber_url: string,
     *         published_at: ?Carbon
     *     }>,
     *     item_count: int,
     *     dilewati: int
     * }
     */
    public function fetch(Sumber $sumber): array
    {
        $config = (array) config('lowongan.scraping.glints', []);

        if (! (bool) ($config['aktif'] ?? true)) {
            throw new RuntimeException('Scraper Glints sedang dinonaktifkan.');
        }

        $listingUrl = trim((string) ($config['listing_url'] ?? ''));

        if ($listingUrl === '') {
            throw new RuntimeException('URL halaman lokasi Glints belum diatur.');
        }

        $html = $this->ambilHtml($listingUrl, $config);
        $data = $this->nextData($html);

        $jobs = $data['props']['pageProps']['initialJobs']['jobsInPage'] ?? null;

        if (! is_array($jobs)) {
            throw new RuntimeException('Struktur data lowongan Glints tidak dikenali.');
        }

        $maks = (int) ($config['maks_item'] ?? 20);
        $maksUmur = (int) config('lowongan.kedaluwarsa_hari', 30);
        $ambilDetail = (bool) ($config['ambil_detail'] ?? true);
        $jeda = max(0, (int) ($config['jeda_ms'] ?? 1000));

        $items = [];
        $dilewati = 0;

        foreach ($jobs as $job) {
            if (! is_array($job) || ! $this->lowonganTangsel($job, $config)) {
                $dilewati++;

                continue;
            }

            if (($job['status'] ?? null) !== 'OPEN') {
                $dilewati++;

                continue;
            }

            $url = $this->urlLowongan($job, $config);

            if ($url === null) {
                $dilewati++;

                continue;
            }

            $published = $this->tanggal($job['createdAt'] ?? null);

            if ($published !== null && $published->lt(now()->subDays($maksUmur))) {
                $dilewati++;

                continue;
            }

            // Lowongan yang sudah pernah diambil tidak perlu dibuka lagi,
            // sehingga permintaan ke situs sumber tetap hemat.
            if (Lowongan::where('url_asli', $url)->exists()) {
                $dilewati++;

                continue;
            }

            $item = $this->itemDariKartu($job, $url, $config);

            if ($ambilDetail) {
                $detail = $this->detail($url, $config);

                if ($detail !== null) {
                    $item = $this->gabungDetail($item, $detail, $config);
                }

                if ($jeda > 0) {
                    usleep($jeda * 1000);
                }
            }

            $items[] = $item;

            if (count($items) >= $maks) {
                break;
            }
        }

        return [
            'items' => $items,
            'item_count' => count($jobs),
            'dilewati' => $dilewati,
        ];
    }

    /**
     * Apakah lowongan berada di Tangerang Selatan menurut data lokasi Glints.
     *
     * @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $config
     */
    private function lowonganTangsel(array $job, array $config): bool
    {
        $lokasi = $job['location'] ?? [];

        if (! is_array($lokasi)) {
            return false;
        }

        $slug = $this->huruf((string) ($lokasi['slug'] ?? ''));
        $nama = $this->huruf((string) ($lokasi['formattedName'] ?? $lokasi['name'] ?? ''));

        if ($slug === 'tangerang-selatan' || $nama === 'tangerang selatan') {
            return true;
        }

        foreach ((array) ($lokasi['parents'] ?? []) as $induk) {
            if (! is_array($induk)) {
                continue;
            }

            $indukSlug = $this->huruf((string) ($induk['slug'] ?? ''));

            if ($indukSlug === 'tangerang-selatan') {
                return true;
            }
        }

        $penanda = array_map(fn ($nama): string => $this->huruf((string) $nama), (array) ($config['kecamatan'] ?? []));

        return $nama !== '' && in_array($nama, $penanda, true);
    }

    /**
     * Nama kecamatan Glints bila halaman menyebutkannya secara eksplisit.
     *
     * @param  array<string, mixed>  $job
     */
    private function kecamatan(array $job): ?string
    {
        $lokasi = $job['location'] ?? [];

        if (! is_array($lokasi)) {
            return null;
        }

        $nama = trim((string) ($lokasi['name'] ?? $lokasi['formattedName'] ?? ''));
        $tingkat = strtolower((string) ($lokasi['administrativeLevelName'] ?? ''));

        if ($nama === '' || ($tingkat !== '' && $tingkat !== 'district')) {
            return null;
        }

        return $nama;
    }

    /**
     * URL halaman detail lowongan. Glints memakai judul sebagai slug dan
     * UUID lowongan sebagai penutup, sehingga URL dapat dibentuk tanpa
     * menebak parameter pencarian.
     *
     * @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $config
     */
    private function urlLowongan(array $job, array $config): ?string
    {
        $id = trim((string) ($job['id'] ?? ''));
        $judul = trim((string) ($job['title'] ?? ''));

        if ($id === '' || $judul === '') {
            return null;
        }

        $slug = Str::slug($judul);

        if ($slug === '') {
            $slug = 'lowongan';
        }

        return rtrim((string) ($config['base_url'] ?? 'https://glints.com'), '/')
            .'/id/opportunities/jobs/'.$slug.'/'.$id;
    }

    /**
     * Data dasar dari kartu lowongan.
     *
     * @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function itemDariKartu(array $job, string $url, array $config): array
    {
        $perusahaan = $job['company']['name'] ?? $job['company']['brandName'] ?? null;

        return [
            'judul' => trim((string) ($job['title'] ?? '')),
            'url' => $url,
            'perusahaan' => is_string($perusahaan) && trim($perusahaan) !== '' ? trim($perusahaan) : null,
            'kecamatan' => $this->kecamatan($job),
            'lokasi' => $this->lokasi($job),
            'jenis' => $this->jenis($job['type'] ?? null),
            'deskripsi' => null,
            'kualifikasi' => null,
            'cara_apply' => null,
            'sumber' => (string) ($config['sumber'] ?? 'Glints'),
            'sumber_url' => (string) ($config['sumber_url'] ?? 'https://glints.com'),
            'published_at' => $this->tanggal($job['createdAt'] ?? null),
        ];
    }

    /**
     * Buka halaman detail untuk melengkapi deskripsi dan kualifikasi.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    private function detail(string $url, array $config): ?array
    {
        try {
            $html = $this->ambilHtml($url, $config);
            $data = $this->nextData($html);
            $job = $data['props']['pageProps']['initialData']['data'] ?? null;

            return is_array($job) ? $job : null;
        } catch (Throwable $e) {
            Log::info('Glints: detail lowongan gagal dibaca '.$url.': '.$e->getMessage());

            return null;
        }
    }

    /**
     * Gabungkan data kartu dengan data halaman detail.
     *
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function gabungDetail(array $item, array $detail, array $config): array
    {
        $bagian = [];

        $gaji = $this->teksGaji($detail['salaries'] ?? null);

        if ($gaji !== null) {
            $bagian[] = $gaji;
        }

        $deskripsi = $this->teksDeskripsi($detail['descriptionJsonString'] ?? null);

        if ($deskripsi !== null) {
            $bagian[] = $deskripsi;
        }

        if ($bagian !== []) {
            $item['deskripsi'] = $this->ringkas(implode("\n\n", $bagian), (int) ($config['panjang_deskripsi'] ?? 1200));
        }

        $kualifikasi = $this->teksKualifikasi($detail);

        if ($kualifikasi !== null) {
            $item['kualifikasi'] = $kualifikasi;
        }

        $item['published_at'] = $this->tanggal($detail['createdAt'] ?? null) ?? $item['published_at'];

        return $item;
    }

    /**
     * Teks deskripsi dari Draft.js JSON milik Glints.
     */
    private function teksDeskripsi(?string $json): ?string
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $data = json_decode($json, true);

        if (! is_array($data) || ! isset($data['blocks']) || ! is_array($data['blocks'])) {
            return $this->ringkas(strip_tags($json), 4000);
        }

        $baris = [];

        foreach ($data['blocks'] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $teks = trim((string) ($block['text'] ?? ''));

            if ($teks !== '') {
                $baris[] = $teks;
            }
        }

        return $baris === [] ? null : implode("\n", $baris);
    }

    /**
     * Gaji dalam format yang mudah dibaca, bila ditampilkan Glints.
     *
     * @param  mixed  $salaries
     */
    private function teksGaji($salaries): ?string
    {
        if (! is_array($salaries) || $salaries === []) {
            return null;
        }

        $gaji = $salaries[0];

        foreach ($salaries as $kandidat) {
            if (is_array($kandidat) && ($kandidat['salaryType'] ?? null) === 'BASIC') {
                $gaji = $kandidat;
                break;
            }
        }

        if (! is_array($gaji)) {
            return null;
        }

        $min = isset($gaji['minAmount']) ? (int) $gaji['minAmount'] : null;
        $max = isset($gaji['maxAmount']) ? (int) $gaji['maxAmount'] : null;

        if (($min === null || $min === 0) && ($max === null || $max === 0)) {
            return null;
        }

        $teks = 'Gaji: ';

        if ($min !== null && $min > 0) {
            $teks .= $this->rupiah($min);
        }

        if ($max !== null && $max > 0 && $max !== $min) {
            $teks .= ($min !== null && $min > 0 ? ' – ' : '').$this->rupiah($max);
        }

        $mode = strtoupper((string) ($gaji['salaryMode'] ?? 'MONTH'));

        return $teks.($mode === 'MONTH' ? ' per bulan' : '');
    }

    /**
     * Kualifikasi ringkas dari field terstruktur Glints.
     *
     * @param  array<string, mixed>  $detail
     */
    private function teksKualifikasi(array $detail): ?string
    {
        $baris = [];

        $pendidikan = $this->pendidikan($detail['educationLevel'] ?? null);

        if ($pendidikan !== null) {
            $baris[] = 'Pendidikan minimal: '.$pendidikan;
        }

        $pengalaman = $this->pengalaman(
            $detail['minYearsOfExperience'] ?? null,
            $detail['maxYearsOfExperience'] ?? null,
        );

        if ($pengalaman !== null) {
            $baris[] = $pengalaman;
        }

        $tipe = $this->labelTipe($detail['type'] ?? null);

        if ($tipe !== null) {
            $baris[] = 'Tipe pekerjaan: '.$tipe;
        }

        $kerja = $this->labelKerja($detail['workArrangementOption'] ?? null);

        if ($kerja !== null) {
            $baris[] = 'Lokasi kerja: '.$kerja;
        }

        return $baris === [] ? null : implode("\n", $baris);
    }

    private function pendidikan(?string $level): ?string
    {
        return match (strtoupper((string) $level)) {
            'ELEMENTARY' => 'SD',
            'JUNIOR_HIGH_SCHOOL' => 'SMP',
            'HIGH_SCHOOL' => 'SMA/SMK',
            'DIPLOMA' => 'Diploma (D1–D4)',
            'BACHELOR' => 'Sarjana (S1)',
            'MASTER' => 'Magister (S2)',
            'DOCTOR' => 'Doktor (S3)',
            default => null,
        };
    }

    private function pengalaman(mixed $min, mixed $max): ?string
    {
        $min = is_numeric($min) ? (int) $min : 0;
        $max = is_numeric($max) ? (int) $max : null;

        if ($min === 0 && ($max === null || $max === 0)) {
            return 'Tidak memerlukan pengalaman';
        }

        if ($max !== null && $max > $min) {
            return "Pengalaman {$min}–{$max} tahun";
        }

        if ($min > 0) {
            return "Pengalaman minimal {$min} tahun";
        }

        return $max !== null ? "Pengalaman kurang dari {$max} tahun" : null;
    }

    private function jenis(?string $type): string
    {
        return match (strtoupper((string) $type)) {
            'INTERNSHIP' => 'magang',
            'PART_TIME', 'FREELANCE' => 'part-time',
            default => 'karir',
        };
    }

    private function labelTipe(?string $type): ?string
    {
        return match (strtoupper((string) $type)) {
            'FULL_TIME' => 'Penuh Waktu',
            'CONTRACT' => 'Kontrak',
            'INTERNSHIP' => 'Magang',
            'PART_TIME' => 'Paruh Waktu',
            'FREELANCE' => 'Freelance',
            'TEMPORARY' => 'Sementara',
            default => null,
        };
    }

    private function labelKerja(?string $option): ?string
    {
        return match (strtoupper((string) $option)) {
            'ONSITE' => 'Kerja di kantor',
            'REMOTE' => 'Remote/dari rumah',
            'HYBRID' => 'Hybrid',
            default => null,
        };
    }

    /**
     * Nama lokasi lengkap untuk keperluan filter wilayah.
     *
     * @param  array<string, mixed>  $job
     */
    private function lokasi(array $job): ?string
    {
        $lokasi = $job['location'] ?? [];

        if (! is_array($lokasi)) {
            return null;
        }

        $bagian = [(string) ($lokasi['name'] ?? '')];

        foreach ((array) ($lokasi['parents'] ?? []) as $induk) {
            if (is_array($induk) && isset($induk['name'])) {
                $bagian[] = (string) $induk['name'];
            }
        }

        $teks = trim(implode(', ', array_filter($bagian)));

        return $teks === '' ? null : $teks;
    }

    private function tanggal(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        try {
            return Carbon::parse($nilai);
        } catch (Throwable) {
            return null;
        }
    }

    private function ringkas(?string $teks, int $maks): ?string
    {
        if ($teks === null) {
            return null;
        }

        $teks = trim((string) preg_replace('/[ \t]+/u', ' ', strip_tags($teks)));

        if ($teks === '') {
            return null;
        }

        if (mb_strlen($teks) <= $maks) {
            return $teks;
        }

        $potong = mb_substr($teks, 0, $maks);
        $spasi = mb_strrpos($potong, ' ');

        if ($spasi !== false) {
            $potong = mb_substr($potong, 0, $spasi);
        }

        return rtrim($potong, " \t\n\r\0\x0B.,;:").'…';
    }

    private function rupiah(int $angka): string
    {
        return 'Rp '.number_format($angka, 0, ',', '.');
    }

    /**
     * Ambil HTML setelah memeriksa robots.txt.
     *
     * @param  array<string, mixed>  $config
     */
    private function ambilHtml(string $url, array $config): string
    {
        if (! $this->robots->allows($url)) {
            throw new RuntimeException('robots.txt melarang pengambilan '.$url);
        }

        $response = Http::withHeaders([
            'User-Agent' => (string) ($config['user_agent'] ?? 'TANGSEL-LIA-Bot/1.0 (+https://tangselia.id)'),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
        ])
            ->timeout((int) ($config['timeout'] ?? 20))
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('HTTP '.$response->status().' untuk '.$url);
        }

        return $response->body();
    }

    /**
     * Baca JSON __NEXT_DATA__ dari halaman Glints.
     *
     * @return array<string, mixed>
     */
    private function nextData(string $html): array
    {
        $crawler = new Crawler($html);
        $script = $crawler->filterXPath('//script[@id="__NEXT_DATA__"]');

        if ($script->count() === 0) {
            throw new RuntimeException('Data __NEXT_DATA__ tidak ditemukan pada halaman Glints.');
        }

        $json = trim($script->first()->text(null, false));

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException('JSON halaman Glints tidak dapat dibaca: '.$e->getMessage());
        }

        if (! is_array($data)) {
            throw new RuntimeException('JSON halaman Glints bukan objek yang dikenali.');
        }

        return $data;
    }

    private function huruf(string $teks): string
    {
        return strtolower(trim((string) preg_replace('/\s+/u', ' ', $teks)));
    }
}
