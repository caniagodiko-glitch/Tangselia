<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Lowongan;
use App\Models\Sumber;
use App\Services\LowonganFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LowonganFetcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.key' => null]);
        Http::preventStrayRequests();

        Kategori::factory()->lowongan()->create([
            'nama' => 'Teknologi dan Informasi',
            'slug' => 'teknologi-dan-informasi',
        ]);
    }

    public function test_lowongan_disimpan_dengan_status_baru_menunggu_kurasi(): void
    {
        Sumber::factory()->lowongan()->create([
            'nama' => 'Lowongan - LinkedIn Jobs (Tangerang Selatan)',
            'feed_url' => 'https://news.google.com/rss/search?q=site%3Alinkedin.com%2Fjobs',
        ]);

        Http::fake(['*' => Http::response($this->lowonganFixture())]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(['sumber' => 1, 'dibuat' => 1, 'dilewati' => 0, 'gagal' => 0], $stats);

        $lowongan = Lowongan::sole();

        $this->assertSame('baru', $lowongan->status);
        $this->assertNull($lowongan->tayang_at);
        $this->assertSame('Content Creator Officer', $lowongan->posisi);
        $this->assertSame('Dexa Group', $lowongan->perusahaan);
        $this->assertTrue($lowongan->relevan);
        $this->assertSame('LGN-000001', $lowongan->kode);
        $this->assertNotNull($lowongan->judul_hash);
    }

    public function test_dedup_melewatkan_item_dengan_url_yang_sama(): void
    {
        Sumber::factory()->lowongan()->create(['feed_url' => 'https://news.google.com/rss/search?q=lowongan']);
        Lowongan::factory()->create(['url_asli' => 'https://news.google.com/rss/articles/abc']);

        Http::fake(['*' => Http::response($this->lowonganFixture())]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['dilewati']);
        $this->assertSame(1, Lowongan::count());
    }

    public function test_dedup_melewatkan_judul_yang_sama_dari_sumber_berbeda(): void
    {
        Sumber::factory()->lowongan()->create(['feed_url' => 'https://news.google.com/rss/search?q=lowongan']);

        Lowongan::factory()->create([
            'judul' => 'Dexa Group hiring Content Creator Officer in Kota Tangerang Selatan, Banten, Indonesia',
            'judul_hash' => sha1('dexa group hiring content creator officer in kota tangerang selatan banten indonesia'),
        ]);

        Http::fake(['*' => Http::response($this->lowonganFixture())]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['dilewati']);
    }

    public function test_sumber_bukan_job_board_tanpa_kata_kunci_ditandai_tidak_relevan(): void
    {
        Sumber::factory()->lowongan()->create([
            'nama' => 'Lowongan Kerja Umum (Tangerang Selatan)',
            'feed_url' => 'https://news.google.com/rss/search?q=%22lowongan+kerja%22',
        ]);

        Http::fake(['*' => Http::response($this->lowonganFixture(
            'Aktor Gary Iskak Meninggal Dunia Akibat Kecelakaan',
            'https://news.google.com/rss/articles/xyz'
        ))]);

        app(LowonganFetcher::class)->run();

        $lowongan = Lowongan::sole();

        $this->assertFalse($lowongan->relevan);
        $this->assertSame('baru', $lowongan->status);
    }

    public function test_deskripsi_diambil_dari_cuplikan_feed(): void
    {
        Sumber::factory()->lowongan()->create(['feed_url' => 'https://news.google.com/rss/search?q=lowongan']);

        Http::fake(['*' => Http::response($this->lowonganFixture())]);

        app(LowonganFetcher::class)->run();

        $lowongan = Lowongan::sole();

        $this->assertStringContainsString('membuka lowongan', (string) $lowongan->deskripsi);
    }

    public function test_sumber_gagal_mencatat_status_error(): void
    {
        Sumber::factory()->lowongan()->create(['feed_url' => 'https://news.google.com/rss/search?q=lowongan']);

        Http::fake(['*' => Http::response('server error', 500)]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(1, $stats['gagal']);
        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame('error', Sumber::sole()->last_status);
    }

    public function test_hanya_sumber_lowongan_yang_diambil(): void
    {
        Sumber::factory()->create([
            'nama' => 'Sumber Berita',
            'tipe' => 'berita',
            'feed_url' => 'https://news.google.com/rss/search?q=berita',
        ]);

        Http::fake(['*' => Http::response($this->lowonganFixture())]);

        $stats = app(LowonganFetcher::class)->run();

        $this->assertSame(0, $stats['sumber']);
        $this->assertSame(0, Lowongan::count());
    }

    private function lowonganFixture(
        string $judul = 'Dexa Group hiring Content Creator Officer in Kota Tangerang Selatan, Banten, Indonesia - LinkedIn',
        string $link = 'https://news.google.com/rss/articles/abc'
    ): string {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
            <channel>
            <title>Google News</title>
            <item>
              <title>{$judul}</title>
              <link>{$link}</link>
              <pubDate>Mon, 05 Oct 2026 08:00:00 +0700</pubDate>
              <description><![CDATA[PT Dexa Group membuka lowongan posisi Content Creator Officer di Kota Tangerang Selatan.]]></description>
              <source url="https://www.linkedin.com">LinkedIn</source>
            </item>
            </channel>
            </rss>
            XML;
    }
}
