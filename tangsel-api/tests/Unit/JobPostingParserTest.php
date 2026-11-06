<?php

namespace Tests\Unit;

use App\Services\Scrapers\JobPostingParser;
use Tests\TestCase;

class JobPostingParserTest extends TestCase
{
    public function test_jobposting_dinormalisasi_dari_json_ld(): void
    {
        $html = $this->halaman([
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => 'Backend Developer',
            'hiringOrganization' => ['name' => 'PT Contoh Teknologi'],
            'jobLocation' => [
                'address' => [
                    'addressLocality' => 'Kota Tangerang Selatan',
                    'addressRegion' => 'Banten',
                    'addressCountry' => 'ID',
                ],
            ],
            'description' => '<p>Membangun API dan menjaga kualitas data.</p><ul><li>PHP</li><li>MySQL</li></ul>',
            'qualifications' => '<p>Memahami REST API.</p>',
            'educationRequirements' => ['credentialCategory' => 'Sarjana (S1)'],
            'experienceRequirements' => ['monthsOfExperience' => 24],
            'employmentType' => 'FULL_TIME',
            'datePosted' => '2026-11-04T05:31:30.000Z',
            'validThrough' => '2026-12-03T17:00:00.000Z',
            'baseSalary' => [
                'currency' => 'IDR',
                'value' => [
                    'minValue' => 5000000,
                    'maxValue' => 7000000,
                    'unitText' => 'MONTH',
                ],
            ],
        ]);

        $item = (new JobPostingParser)->parse($html, 'https://contoh.test/lowongan/backend-developer');

        $this->assertNotNull($item);
        $this->assertSame('Backend Developer', $item['judul']);
        $this->assertSame('PT Contoh Teknologi', $item['perusahaan']);
        $this->assertSame('Kota Tangerang Selatan, Banten, ID', $item['lokasi']);
        $this->assertNull($item['kecamatan']);
        $this->assertSame('karir', $item['jenis']);
        $this->assertSame('https://contoh.test/lowongan/backend-developer', $item['url']);
        $this->assertStringContainsString('Membangun API', $item['deskripsi']);
        $this->assertStringContainsString('Gaji: Rp 5.000.000 – Rp 7.000.000 per bulan', $item['deskripsi']);
        $this->assertStringContainsString('Memahami REST API', $item['kualifikasi']);
        $this->assertStringContainsString('Pendidikan: Sarjana (S1)', $item['kualifikasi']);
        $this->assertStringContainsString('Pengalaman minimal 2 tahun', $item['kualifikasi']);
        $this->assertSame('2026-11-04 05:31:30', $item['published_at']->toDateTimeString());
        $this->assertSame('2026-12-03 17:00:00', $item['kedaluwarsa_at']->toDateTimeString());
    }

    public function test_kecamatan_diambil_bila_lokasi_memang_kecamatan(): void
    {
        $html = $this->halaman([
            '@type' => 'JobPosting',
            'title' => 'Barista',
            'jobLocation' => [
                'address' => [
                    'addressLocality' => 'Serpong',
                    'addressRegion' => 'Banten',
                ],
            ],
            'employmentType' => 'PART_TIME',
            'datePosted' => '2026-11-04',
        ]);

        $item = (new JobPostingParser)->parse($html, 'https://contoh.test/lowongan/barista');

        $this->assertNotNull($item);
        $this->assertSame('Serpong', $item['kecamatan']);
        $this->assertSame('part-time', $item['jenis']);
    }

    public function test_halaman_tanpa_jobposting_dikembalikan_null(): void
    {
        $html = $this->halaman([
            '@type' => 'Article',
            'headline' => 'Bukan lowongan',
        ]);

        $this->assertNull((new JobPostingParser)->parse($html, 'https://contoh.test/artikel'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function halaman(array $data): string
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<!DOCTYPE html><html><head><script type="application/ld+json">'
            .$json.'</script></head><body></body></html>';
    }
}
