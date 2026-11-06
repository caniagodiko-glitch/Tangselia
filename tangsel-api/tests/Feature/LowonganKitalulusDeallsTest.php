<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Lowongan;
use App\Models\Sumber;
use App\Services\LowonganFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LowonganKitalulusDeallsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'lowongan.scraping.kitalulus.aktif' => true,
            'lowongan.scraping.kitalulus.jeda_ms' => 0,
            'lowongan.scraping.dealls.aktif' => true,
            'lowongan.scraping.dealls.jeda_ms' => 0,
        ]);

        Kategori::factory()->lowongan()->create([
            'nama' => 'Teknologi dan Informasi',
            'slug' => 'teknologi-dan-informasi',
        ]);
    }

    public function test_kitalulus_menyimpan_lowongan_tangsel_dengan_link_asli(): void
    {
        $sumber = $this->sumberKitalulus();
        $urlDetail = 'https://www.kitalulus.com/lowongan/detail/backend-developer-abc123';

        Http::fake([
            'https://www.kitalulus.com/robots.txt' => Http::response("User-agent: *\nDisallow: /auth/\n"),
            'https://www.kitalulus.com/lowongan/in-kota-tangerang-selatan' => Http::response(
                '<html><body><a href="/lowongan/detail/backend-developer-abc123">Backend Developer</a></body></html>'
            ),
            'https://www.kitalulus.com/lowongan/detail/*' => Http::response($this->halamanDetail([
                'title' => 'Backend Developer',
                'hiringOrganization' => ['name' => 'PT Contoh Teknologi'],
                'jobLocation' => [
                    'address' => [
                        'addressLocality' => 'Kota Tangerang Selatan',
                        'addressRegion' => 'Banten',
                        'addressCountry' => 'ID',
                    ],
                ],
                'description' => '<p>Membangun API dan menjaga kualitas data.</p>',
                'employmentType' => 'FULL_TIME',
                'datePosted' => now()->subDay()->toIso8601ZuluString(),
                'validThrough' => now()->addDays(10)->toIso8601ZuluString(),
                'baseSalary' => [
                    'currency' => 'IDR',
                    'value' => ['minValue' => 5000000, 'maxValue' => 7000000, 'unitText' => 'MONTH'],
                ],
            ])),
        ]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(['sumber' => 1, 'dibuat' => 1, 'dilewati' => 0, 'gagal' => 0], $stats);

        $lowongan = Lowongan::sole();

        $this->assertSame($sumber->id, $lowongan->sumber_id);
        $this->assertSame('Backend Developer', $lowongan->judul);
        $this->assertSame('PT Contoh Teknologi', $lowongan->perusahaan);
        $this->assertSame($urlDetail, $lowongan->url_asli);
        $this->assertSame('Kitalulus', $lowongan->sumber);
        $this->assertSame('https://www.kitalulus.com', $lowongan->sumber_url);
        $this->assertSame('baru', $lowongan->status);
        $this->assertTrue($lowongan->relevan);
        $this->assertStringContainsString('Membangun API', (string) $lowongan->deskripsi);
        $this->assertStringContainsString('Gaji: Rp 5.000.000 – Rp 7.000.000 per bulan', (string) $lowongan->deskripsi);
        $this->assertNotNull($lowongan->published_at);
        $this->assertNotNull($lowongan->kedaluwarsa_at);
    }

    public function test_kitalulus_menolak_lowongan_yang_berada_di_luar_tangsel(): void
    {
        $this->sumberKitalulus();
        $urlDetail = 'https://www.kitalulus.com/lowongan/detail/sales-kota-tangerang';

        Http::fake([
            'https://www.kitalulus.com/robots.txt' => Http::response("User-agent: *\nDisallow: /auth/\n"),
            'https://www.kitalulus.com/lowongan/in-kota-tangerang-selatan' => Http::response(
                '<html><body><a href="/lowongan/detail/sales-kota-tangerang">Sales</a></body></html>'
            ),
            'https://www.kitalulus.com/lowongan/detail/*' => Http::response($this->halamanDetail([
                'title' => 'Sales',
                'jobLocation' => [
                    'address' => ['addressLocality' => 'Kota Tangerang', 'addressRegion' => 'Banten'],
                ],
                'datePosted' => now()->subDay()->toIso8601ZuluString(),
            ])),
        ]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(1, $stats['sumber']);
        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['dilewati']);
        $this->assertSame(0, $stats['gagal']);
        $this->assertSame(0, Lowongan::count());
    }

    public function test_dealls_hanya_mengambil_url_tangsel_dan_memakai_json_ld(): void
    {
        $sumber = $this->sumberDealls();
        $urlTangsel = 'https://dealls.com/loker/backend-developer-tangerang-selatan~contoh';
        $urlJakarta = 'https://dealls.com/loker/backend-developer-jakarta~contoh';

        Http::fake([
            'https://dealls.com/robots.txt' => Http::response("User-agent: *\nAllow: /\n"),
            'https://dealls.com/sitemap-dynamic.xml' => Http::response($this->sitemap([
                $urlTangsel,
                $urlJakarta,
                'https://dealls.com/en/loker/backend-developer-tangerang-selatan~contoh',
                'https://dealls.com/loker/lokasi/loker-tangerang-selatan',
            ])),
            'https://dealls.com/loker/backend-developer-tangerang-selatan*' => Http::response($this->halamanDetail([
                'title' => 'Backend Developer',
                'hiringOrganization' => ['name' => 'PT Contoh Teknologi'],
                'jobLocation' => [
                    'address' => [
                        'addressLocality' => 'Tangerang Selatan',
                        'addressRegion' => 'Banten',
                    ],
                ],
                'description' => '<p>Membangun API untuk produk lokal.</p>',
                'employmentType' => 'FULL_TIME',
                'datePosted' => now()->subDay()->toIso8601ZuluString(),
            ])),
        ]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(['sumber' => 1, 'dibuat' => 1, 'dilewati' => 0, 'gagal' => 0], $stats);

        $lowongan = Lowongan::sole();

        $this->assertSame($sumber->id, $lowongan->sumber_id);
        $this->assertSame($urlTangsel, $lowongan->url_asli);
        $this->assertSame('Dealls', $lowongan->sumber);
        $this->assertSame('https://dealls.com', $lowongan->sumber_url);
        $this->assertSame('baru', $lowongan->status);
        $this->assertTrue($lowongan->relevan);
        $this->assertStringContainsString('Membangun API', (string) $lowongan->deskripsi);
        $this->assertNotNull($lowongan->published_at);
    }

    public function test_dealls_melewati_lowongan_yang_sudah_kedaluwarsa(): void
    {
        $this->sumberDealls();
        $url = 'https://dealls.com/loker/lowongan-lama-tangerang-selatan~contoh';

        Http::fake([
            'https://dealls.com/robots.txt' => Http::response("User-agent: *\nAllow: /\n"),
            'https://dealls.com/sitemap-dynamic.xml' => Http::response($this->sitemap([$url])),
            'https://dealls.com/loker/*' => Http::response($this->halamanDetail([
                'title' => 'Lowongan Lama',
                'jobLocation' => [
                    'address' => ['addressLocality' => 'Tangerang Selatan'],
                ],
                'datePosted' => '2024-10-14T00:00:00.000Z',
            ])),
        ]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(1, $stats['sumber']);
        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['dilewati']);
        $this->assertSame(0, $stats['gagal']);
        $this->assertSame(0, Lowongan::count());
    }

    private function sumberKitalulus(): Sumber
    {
        return Sumber::factory()->lowongan()->create([
            'nama' => 'Kitalulus - Lowongan Tangerang Selatan',
            'jenis' => 'scraping',
            'feed_url' => 'https://www.kitalulus.com/lowongan/in-kota-tangerang-selatan',
        ]);
    }

    private function sumberDealls(): Sumber
    {
        return Sumber::factory()->lowongan()->create([
            'nama' => 'Dealls - Lowongan Tangerang Selatan',
            'jenis' => 'scraping',
            'feed_url' => 'https://dealls.com/sitemap-dynamic.xml',
        ]);
    }

    /**
     * @param  array<string, mixed>  $ganti
     */
    private function halamanDetail(array $ganti = []): string
    {
        $data = array_replace([
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => 'Backend Developer',
            'hiringOrganization' => ['name' => 'PT Contoh Teknologi'],
            'jobLocation' => [
                'address' => [
                    'addressLocality' => 'Tangerang Selatan',
                    'addressRegion' => 'Banten',
                ],
            ],
            'description' => '<p>Deskripsi lowongan.</p>',
            'employmentType' => 'FULL_TIME',
            'datePosted' => now()->subDay()->toIso8601ZuluString(),
            'validThrough' => now()->addDays(10)->toIso8601ZuluString(),
        ], $ganti);

        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<!DOCTYPE html><html><head><script type="application/ld+json">'
            .$json.'</script></head><body></body></html>';
    }

    /**
     * @param  list<string>  $urls
     */
    private function sitemap(array $urls): string
    {
        $isi = '';

        foreach ($urls as $url) {
            $isi .= '<url><loc>'.htmlspecialchars($url, ENT_XML1).'</loc></url>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$isi.'</urlset>';
    }
}
