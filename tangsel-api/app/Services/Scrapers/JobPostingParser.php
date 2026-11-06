<?php

namespace App\Services\Scrapers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Pembaca data lowongan dari JSON-LD schema.org JobPosting.
 *
 * Kitalulus dan Dealls menempelkan data lowongan sebagai JSON-LD pada
 * halaman detail. Format ini jauh lebih stabil daripada kelas CSS dan
 * memuat deskripsi, perusahaan, lokasi, tanggal, serta masa berlaku.
 */
class JobPostingParser
{
    /**
     * Ambil satu JobPosting dari HTML halaman detail.
     *
     * @return array{
     *     judul: string,
     *     url: string,
     *     perusahaan: ?string,
     *     lokasi: ?string,
     *     kecamatan: ?string,
     *     jenis: ?string,
     *     deskripsi: ?string,
     *     kualifikasi: ?string,
     *     cara_apply: ?string,
     *     published_at: ?Carbon,
     *     kedaluwarsa_at: ?Carbon
     * }|null
     */
    public function parse(string $html, string $url): ?array
    {
        $posting = $this->postingDariHtml($html);

        if ($posting === null) {
            return null;
        }

        $judul = $this->teks($posting['title'] ?? null);

        if ($judul === '') {
            return null;
        }

        $gaji = $this->gaji($posting['baseSalary'] ?? null);
        $deskripsi = $this->teks($posting['description'] ?? null);
        $deskripsi = $this->gabung($gaji, $deskripsi, 6000);
        $alamat = $this->alamat($posting['jobLocation'] ?? null);
        $perusahaan = is_array($posting['hiringOrganization'] ?? null)
            ? $this->teks($posting['hiringOrganization']['name'] ?? null)
            : '';

        return [
            'judul' => $this->potong($judul, 255),
            'url' => $url,
            'perusahaan' => $this->nullable($this->potong($perusahaan, 255)),
            'lokasi' => $alamat['lokasi'],
            'kecamatan' => $alamat['kecamatan'],
            'jenis' => $this->jenis($posting['employmentType'] ?? null),
            'deskripsi' => $deskripsi,
            'kualifikasi' => $this->kualifikasi($posting),
            'cara_apply' => null,
            'published_at' => $this->tanggal($posting['datePosted'] ?? null),
            'kedaluwarsa_at' => $this->tanggal($posting['validThrough'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function postingDariHtml(string $html): ?array
    {
        $crawler = new Crawler($html);

        foreach ($crawler->filterXPath('//script[contains(@type, "ld+json")]') as $node) {
            $json = trim((string) $node->textContent);

            if ($json === '') {
                continue;
            }

            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                continue;
            }

            $posting = $this->cariPosting($data);

            if ($posting !== null) {
                return $posting;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cariPosting(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        $tipe = $data['@type'] ?? null;
        $daftarTipe = is_array($tipe) ? $tipe : [$tipe];

        foreach ($daftarTipe as $satu) {
            if (is_string($satu) && strcasecmp($satu, 'JobPosting') === 0) {
                return $data;
            }
        }

        foreach ($data as $nilai) {
            $posting = $this->cariPosting($nilai);

            if ($posting !== null) {
                return $posting;
            }
        }

        return null;
    }

    /**
     * @return array{lokasi: ?string, kecamatan: ?string}
     */
    private function alamat(mixed $jobLocation): array
    {
        foreach ($this->daftar($jobLocation) as $lokasi) {
            if (! is_array($lokasi)) {
                continue;
            }

            $alamat = $lokasi['address'] ?? $lokasi;

            if (! is_array($alamat)) {
                continue;
            }

            $local = $this->teks($alamat['addressLocality'] ?? null);
            $region = $this->teks($alamat['addressRegion'] ?? null);
            $negara = $this->teks($alamat['addressCountry'] ?? null);

            $bagian = array_values(array_filter([$local, $region, $negara], fn (string $satu): bool => $satu !== ''));

            if ($bagian === []) {
                continue;
            }

            return [
                'lokasi' => $this->potong(implode(', ', $bagian), 255),
                'kecamatan' => $this->kecamatan($local),
            ];
        }

        return ['lokasi' => null, 'kecamatan' => null];
    }

    /**
     * Nama kecamatan hanya dipakai bila lokasi memang berupa kecamatan,
     * bukan nama kota/kabupaten.
     */
    private function kecamatan(string $local): ?string
    {
        $huruf = Str::lower($local);

        if ($local === '' || Str::contains($huruf, ['tangerang selatan', 'kota tangerang', 'kabupaten tangerang'])) {
            return null;
        }

        return $this->potong($local, 100);
    }

    private function kualifikasi(array $posting): ?string
    {
        $bagian = [];

        $kualifikasi = $this->teks($posting['qualifications'] ?? null);

        if ($kualifikasi !== '') {
            $bagian[] = $kualifikasi;
        }

        $pendidikan = $this->pendidikan($posting['educationRequirements'] ?? null);

        if ($pendidikan !== '') {
            $bagian[] = 'Pendidikan: '.$pendidikan;
        }

        $pengalaman = $this->pengalaman($posting['experienceRequirements'] ?? null);

        if ($pengalaman !== null) {
            $bagian[] = $pengalaman;
        }

        $tipe = $this->labelTipe($posting['employmentType'] ?? null);

        if ($tipe !== null) {
            $bagian[] = 'Tipe pekerjaan: '.$tipe;
        }

        if ($bagian === []) {
            return null;
        }

        return $this->potong(implode("\n", $bagian), 2000);
    }

    private function pendidikan(mixed $nilai): string
    {
        if (is_string($nilai)) {
            return $this->teks($nilai);
        }

        if (! is_array($nilai)) {
            return '';
        }

        if (array_is_list($nilai)) {
            $bagian = [];

            foreach ($nilai as $satu) {
                $teks = $this->pendidikan($satu);

                if ($teks !== '') {
                    $bagian[] = $teks;
                }
            }

            return implode("\n", array_values(array_unique($bagian)));
        }

        $bagian = [];

        foreach (['credentialCategory', 'educationalCredentialAwarded', 'name'] as $kunci) {
            $teks = $this->teks($nilai[$kunci] ?? null);

            if ($teks !== '') {
                $bagian[] = $teks;
            }
        }

        if ($bagian === []) {
            foreach ($nilai as $kunci => $satu) {
                if (str_starts_with((string) $kunci, '@')) {
                    continue;
                }

                $teks = $this->teks($satu);

                if ($teks !== '') {
                    $bagian[] = $teks;
                }
            }
        }

        return implode(', ', array_values(array_unique($bagian)));
    }

    private function pengalaman(mixed $pengalaman): ?string
    {
        if (! is_array($pengalaman)) {
            return null;
        }

        $bulan = $pengalaman['monthsOfExperience'] ?? null;

        if (! is_numeric($bulan) || (int) $bulan <= 0) {
            return null;
        }

        $bulan = (int) $bulan;

        if ($bulan % 12 === 0) {
            return 'Pengalaman minimal '.($bulan / 12).' tahun';
        }

        return 'Pengalaman minimal '.$bulan.' bulan';
    }

    private function jenis(mixed $employmentType): ?string
    {
        $tipe = $this->tipePertama($employmentType);

        return match (strtoupper($tipe)) {
            'INTERN', 'INTERNSHIP' => 'magang',
            'PART_TIME', 'PART-TIME', 'PARTTIME' => 'part-time',
            'FULL_TIME', 'CONTRACTOR', 'CONTRACT', 'TEMPORARY', 'VOLUNTEER' => 'karir',
            default => null,
        };
    }

    private function labelTipe(mixed $employmentType): ?string
    {
        $tipe = $this->tipePertama($employmentType);

        return match (strtoupper($tipe)) {
            'FULL_TIME' => 'Penuh Waktu',
            'CONTRACTOR', 'CONTRACT' => 'Kontrak',
            'INTERN', 'INTERNSHIP' => 'Magang',
            'PART_TIME', 'PART-TIME', 'PARTTIME' => 'Paruh Waktu',
            'TEMPORARY' => 'Sementara',
            'VOLUNTEER' => 'Sukarela',
            default => null,
        };
    }

    private function tipePertama(mixed $employmentType): string
    {
        if (is_array($employmentType)) {
            $employmentType = $employmentType[0] ?? '';
        }

        return is_string($employmentType) ? $employmentType : '';
    }

    private function gaji(mixed $baseSalary): ?string
    {
        if (! is_array($baseSalary)) {
            return null;
        }

        $nilai = $baseSalary['value'] ?? $baseSalary;
        $mataUang = $this->teks($baseSalary['currency'] ?? null);

        if (! is_array($nilai)) {
            return null;
        }

        $min = is_numeric($nilai['minValue'] ?? null) ? (int) $nilai['minValue'] : null;
        $max = is_numeric($nilai['maxValue'] ?? null) ? (int) $nilai['maxValue'] : null;

        if (($min === null || $min <= 0) && ($max === null || $max <= 0)) {
            return null;
        }

        $teks = 'Gaji: ';

        if ($min !== null && $min > 0) {
            $teks .= $this->rupiah($min, $mataUang);
        }

        if ($max !== null && $max > 0 && $max !== $min) {
            $teks .= ($min !== null && $min > 0 ? ' – ' : '').$this->rupiah($max, $mataUang);
        }

        $satuan = Str::lower($this->teks($nilai['unitText'] ?? null));

        if ($satuan === 'month' || $satuan === 'monthly' || $satuan === 'bulan') {
            $teks .= ' per bulan';
        }

        return $teks;
    }

    private function rupiah(int $angka, string $mataUang): string
    {
        $prefix = strtoupper($mataUang) === 'IDR' || $mataUang === '' ? 'Rp ' : strtoupper($mataUang).' ';

        return $prefix.number_format($angka, 0, ',', '.');
    }

    private function gabung(?string $awal, string $akhir, int $maks): ?string
    {
        $bagian = array_values(array_filter([$awal, $akhir], fn (?string $satu): bool => $satu !== null && trim($satu) !== ''));

        if ($bagian === []) {
            return null;
        }

        return $this->potong(implode("\n\n", $bagian), $maks);
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

    private function teks(mixed $nilai): string
    {
        if (is_array($nilai)) {
            return $this->teks($this->teksArray($nilai));
        }

        if (! is_string($nilai) || trim($nilai) === '') {
            return '';
        }

        $nilai = html_entity_decode($nilai, ENT_QUOTES | ENT_HTML5);
        $nilai = (string) preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $nilai);
        $nilai = (string) preg_replace('/<\s*\/\s*(p|li|ul|ol|div|h[1-6])\s*>/i', "\n", $nilai);
        $nilai = strip_tags($nilai);

        $baris = preg_split('/\R/u', $nilai) ?: [];
        $bersih = [];

        foreach ($baris as $satu) {
            $satu = trim((string) preg_replace('/[ \t]+/u', ' ', $satu));

            if ($satu !== '') {
                $bersih[] = $satu;
            }
        }

        return trim(implode("\n", $bersih));
    }

    /**
     * @param  array<mixed>  $nilai
     */
    private function teksArray(array $nilai): string
    {
        $bagian = [];

        foreach ($nilai as $satu) {
            if (is_array($satu)) {
                $satu = $this->teksArray($satu);
            }

            if (is_string($satu) && trim($satu) !== '') {
                $bagian[] = $satu;
            }
        }

        return implode("\n", $bagian);
    }

    /**
     * @return list<mixed>
     */
    private function daftar(mixed $nilai): array
    {
        if (! is_array($nilai)) {
            return [];
        }

        if (array_is_list($nilai)) {
            return $nilai;
        }

        return [$nilai];
    }

    private function potong(string $teks, int $maks): string
    {
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

    private function nullable(string $teks): ?string
    {
        return trim($teks) === '' ? null : trim($teks);
    }
}
