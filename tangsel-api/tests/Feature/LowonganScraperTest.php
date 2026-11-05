<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Lowongan;
use App\Models\Sumber;
use App\Services\LowonganFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LowonganScraperTest extends TestCase
{
    use RefreshDatabase;

    private const URL_DAFTAR = 'https://glints.com/id/job-location/indonesia/banten/tangerang-selatan';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'lowongan.scraping.glints.aktif' => true,
            'lowongan.scraping.glints.jeda_ms' => 0,
        ]);
    }

    public function test_lowongan_glints_disimpan_dengan_link_asli_dan_data_terstruktur(): void
    {
        $kategori = Kategori::factory()->lowongan()->create([
            'nama' => 'Teknologi dan Informasi',
            'slug' => 'teknologi-dan-informasi',
        ]);
        $serpong = Kecamatan::factory()->create(['nama' => 'Serpong', 'slug' => 'serpong']);

        $sumber = $this->sumberGlints();

        Http::fake([
            'https://glints.com/robots.txt' => Http::response("User-agent: *\nAllow: /\n"),
            'https://glints.com/id/job-location/*' => Http::response($this->halamanDaftar([$this->pekerjaan()])),
            'https://glints.com/id/opportunities/jobs/*' => Http::response($this->halamanDetail($this->detailUtama())),
        ]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(1, $stats['sumber']);
        $this->assertSame(1, $stats['dibuat']);
        $this->assertSame(0, $stats['gagal']);

        $lowongan = Lowongan::sole();

        $this->assertSame($sumber->id, $lowongan->sumber_id);
        $this->assertSame('Backend Developer', $lowongan->judul);
        $this->assertSame('PT Contoh Teknologi', $lowongan->perusahaan);
        $this->assertSame(
            'https://glints.com/id/opportunities/jobs/backend-developer/11111111-1111-1111-1111-111111111111',
            $lowongan->url_asli
        );
        $this->assertSame('Glints', $lowongan->sumber);
        $this->assertSame('https://glints.com', $lowongan->sumber_url);
        $this->assertSame('baru', $lowongan->status);
        $this->assertNull($lowongan->tayang_at);
        $this->assertSame($serpong->id, $lowongan->kecamatan_id);
        $this->assertSame($kategori->id, $lowongan->kategori_id);
        $this->assertSame('karir', $lowongan->jenis);
        $this->assertTrue($lowongan->relevan);
        $this->assertNotNull($lowongan->published_at);

        $this->assertStringContainsString('Gaji: Rp 5.000.000 – Rp 7.000.000 per bulan', $lowongan->deskripsi);
        $this->assertStringContainsString('Bertanggung jawab membangun API', $lowongan->deskripsi);
        $this->assertStringContainsString('Pendidikan minimal: Sarjana (S1)', $lowongan->kualifikasi);
        $this->assertStringContainsString('Pengalaman 1–3 tahun', $lowongan->kualifikasi);
    }

    public function test_lowongan_di_luar_tangerang_selatan_dilewati(): void
    {
        $this->sumberGlints();

        $pekerjaan = $this->pekerjaan([
            'id' => '22222222-2222-2222-2222-222222222222',
            'title' => 'Accounting Staff',
            'location' => [
                'name' => 'Kebayoran Baru',
                'formattedName' => 'Kebayoran Baru',
                'slug' => 'kebayoran-baru',
                'administrativeLevelName' => 'District',
                'parents' => [
                    ['name' => 'Jakarta Selatan', 'slug' => 'jakarta-selatan', 'administrativeLevelName' => 'City'],
                ],
            ],
        ]);

        Http::fake([
            'https://glints.com/robots.txt' => Http::response("User-agent: *\nAllow: /\n"),
            'https://glints.com/id/job-location/*' => Http::response($this->halamanDaftar([$pekerjaan])),
            'https://glints.com/id/opportunities/jobs/*' => Http::response($this->halamanDetail($this->detailUtama())),
        ]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(1, $stats['sumber']);
        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['dilewati']);
        $this->assertSame(0, Lowongan::count());
    }

    public function test_lowongan_glints_yang_sudah_ada_tidak_diambil_ulang(): void
    {
        $sumber = $this->sumberGlints();

        Lowongan::factory()->create([
            'sumber_id' => $sumber->id,
            'url_asli' => 'https://glints.com/id/opportunities/jobs/backend-developer/11111111-1111-1111-1111-111111111111',
        ]);

        Http::fake([
            'https://glints.com/robots.txt' => Http::response("User-agent: *\nAllow: /\n"),
            'https://glints.com/id/job-location/*' => Http::response($this->halamanDaftar([$this->pekerjaan()])),
            'https://glints.com/id/opportunities/jobs/*' => Http::response($this->halamanDetail($this->detailUtama())),
        ]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['dilewati']);
        $this->assertSame(1, Lowongan::count());
    }

    public function test_halaman_yang_dilarang_robots_txt_tidak_diambil(): void
    {
        $sumber = $this->sumberGlints();

        Http::fake([
            'https://glints.com/robots.txt' => Http::response("User-agent: *\nDisallow: /id/job-location/\n"),
            'https://glints.com/id/job-location/*' => Http::response($this->halamanDaftar([$this->pekerjaan()])),
        ]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(1, $stats['sumber']);
        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['gagal']);
        $this->assertSame(0, Lowongan::count());
        $this->assertSame('error', $sumber->fresh()->last_status);

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/id/job-location/'));
    }

    private function sumberGlints(): Sumber
    {
        return Sumber::factory()->lowongan()->create([
            'nama' => 'Lowongan - Glints (Tangerang Selatan)',
            'jenis' => 'scraping',
            'feed_url' => self::URL_DAFTAR,
        ]);
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function pekerjaan(array $ganti = []): array
    {
        $dasar = [
            '__typename' => 'Job',
            'id' => '11111111-1111-1111-1111-111111111111',
            'title' => 'Backend Developer',
            'status' => 'OPEN',
            'createdAt' => now()->subDay()->toIso8601ZuluString(),
            'type' => 'FULL_TIME',
            'educationLevel' => 'BACHELOR',
            'workArrangementOption' => 'ONSITE',
            'minYearsOfExperience' => 1,
            'maxYearsOfExperience' => 3,
            'company' => ['name' => 'PT Contoh Teknologi'],
            'location' => [
                'name' => 'Serpong',
                'formattedName' => 'Serpong',
                'slug' => 'serpong',
                'administrativeLevelName' => 'District',
                'parents' => [
                    ['name' => 'Tangerang Selatan', 'slug' => 'tangerang-selatan', 'administrativeLevelName' => 'City'],
                ],
            ],
        ];

        return array_replace($dasar, $ganti);
    }

    /**
     * @return array<string, mixed>
     */
    private function detailUtama(): array
    {
        return [
            'title' => 'Backend Developer',
            'descriptionJsonString' => json_encode([
                'blocks' => [
                    ['text' => 'Kami mencari Backend Developer untuk tim produk.'],
                    ['text' => 'Bertanggung jawab membangun API dan menjaga kualitas data.'],
                ],
            ], JSON_THROW_ON_ERROR),
            'educationLevel' => 'BACHELOR',
            'minYearsOfExperience' => 1,
            'maxYearsOfExperience' => 3,
            'type' => 'FULL_TIME',
            'workArrangementOption' => 'ONSITE',
            'salaries' => [[
                'salaryType' => 'BASIC',
                'minAmount' => 5000000,
                'maxAmount' => 7000000,
                'salaryMode' => 'MONTH',
                'CurrencyCode' => 'IDR',
            ]],
            'createdAt' => now()->subDay()->toIso8601ZuluString(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     */
    private function halamanDaftar(array $jobs): string
    {
        return $this->halamanNext([
            'props' => [
                'pageProps' => [
                    'initialJobs' => [
                        'jobsInPage' => $jobs,
                        'hasMore' => false,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function halamanDetail(array $data): string
    {
        return $this->halamanNext([
            'props' => [
                'pageProps' => [
                    'initialData' => [
                        'code' => 'OK',
                        'data' => $data,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function halamanNext(array $data): string
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<!DOCTYPE html><html><body><script id="__NEXT_DATA__" type="application/json">'
            .$json.'</script></body></html>';
    }
}
