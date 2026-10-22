<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Sumber;
use App\Services\NewsFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsFetcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.key' => null]);
        Http::preventStrayRequests();

        Kategori::factory()->create([
            'scope' => 'berita',
            'nama' => 'Berita Kota',
            'slug' => 'berita-kota',
            'urutan' => 1,
        ]);
    }

    public function test_item_rss_baru_disimpan_dengan_ringkasan_feed(): void
    {
        $sumber = Sumber::factory()->create([
            'nama' => 'ANTARA News Banten',
            'feed_url' => 'https://banten.antaranews.com/rss/terkini.xml',
        ]);

        Http::fake(['*' => Http::response($this->rssFixture())]);

        $stats = app(NewsFetcher::class)->run();

        $this->assertSame(['sumber' => 1, 'dibuat' => 1, 'dilewati' => 0, 'gagal' => 0], $stats);

        $berita = Berita::sole();

        $this->assertSame('agregat', $berita->tipe);
        $this->assertSame('published', $berita->status);
        $this->assertSame('https://banten.antaranews.com/berita/normalisasi-pamulang', $berita->url_asli);
        $this->assertSame('feed', $berita->diringkas_oleh);
        $this->assertSame('ANTARA News Banten', $berita->sumber);
        $this->assertStringContainsString('normalisasi saluran', $berita->ringkasan);
        $this->assertSame('https://antaranews.example/img-pamulang.jpg', $berita->gambar);
        $this->assertNotNull($berita->published_at);

        $this->assertSame('ok', $sumber->fresh()->last_status);
        $this->assertSame(1, $sumber->fresh()->item_count);
        $this->assertNotNull($sumber->fresh()->last_fetched_at);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'generativelanguage'));
    }

    public function test_dedup_melewatkan_item_yang_sudah_ada(): void
    {
        Sumber::factory()->create(['feed_url' => 'https://banten.antaranews.com/rss/terkini.xml']);
        Berita::factory()->agregat()->create([
            'url_asli' => 'https://banten.antaranews.com/berita/normalisasi-pamulang',
        ]);

        Http::fake(['*' => Http::response($this->rssFixture())]);

        $stats = app(NewsFetcher::class)->run();

        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['dilewati']);
        $this->assertSame(1, Berita::count());
    }

    public function test_url_asli_bing_news_diekstrak_dan_atribusi_sumber_diambil(): void
    {
        Sumber::factory()->create([
            'nama' => 'Bing News - Tangerang Selatan',
            'feed_url' => 'https://www.bing.com/news/search?q=tangsel&format=rss',
        ]);

        Http::fake(['*' => Http::response($this->bingFixture())]);

        app(NewsFetcher::class)->run();

        $berita = Berita::sole();

        $this->assertSame('https://www.rm.id/baca/berita/serpong-pemain-baru', $berita->url_asli);
        $this->assertSame('RMID', $berita->sumber);
        $this->assertSame('https://rm.id', $berita->sumber_url);
    }

    public function test_ringkasan_ai_dipakai_ketika_gemini_berhasil(): void
    {
        config(['ai.key' => 'kunci-uji']);

        Sumber::factory()->create(['feed_url' => 'https://banten.antaranews.com/rss/terkini.xml']);

        Http::fake([
            'https://banten.antaranews.com/rss/*' => Http::response($this->rssFixture()),
            'https://banten.antaranews.com/robots.txt' => Http::response("User-agent: *\nAllow: /"),
            'https://banten.antaranews.com/berita/*' => Http::response(
                '<html><body><h1>Judul</h1><p>'.str_repeat('Normalisasi saluran di Pamulang selesai minggu ini. ', 20).'</p></body></html>'
            ),
            'https://generativelanguage.googleapis.com/*' => Http::response(json_encode([
                'candidates' => [['content' => ['parts' => [['text' => 'Ringkasan AI singkat dari artikel.']]]]],
            ]), 200, ['Content-Type' => 'application/json']),
            '*' => Http::response('', 404),
        ]);

        app(NewsFetcher::class)->run();

        $berita = Berita::sole();

        $this->assertSame('ai', $berita->diringkas_oleh);
        $this->assertSame('Ringkasan AI singkat dari artikel.', $berita->ringkasan);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));
    }

    public function test_fallback_ringkasan_feed_ketika_gemini_gagal(): void
    {
        config(['ai.key' => 'kunci-uji']);

        Sumber::factory()->create(['feed_url' => 'https://banten.antaranews.com/rss/terkini.xml']);

        Http::fake([
            'https://banten.antaranews.com/rss/*' => Http::response($this->rssFixture()),
            'https://banten.antaranews.com/robots.txt' => Http::response("User-agent: *\nAllow: /"),
            'https://banten.antaranews.com/berita/*' => Http::response(
                '<html><body><p>'.str_repeat('Isi artikel normalisasi saluran Pamulang. ', 20).'</p></body></html>'
            ),
            'https://generativelanguage.googleapis.com/*' => Http::response('rate limited', 429),
            '*' => Http::response('', 404),
        ]);

        app(NewsFetcher::class)->run();

        $berita = Berita::sole();

        $this->assertSame('feed', $berita->diringkas_oleh);
        $this->assertStringContainsString('normalisasi saluran', $berita->ringkasan);
    }

    public function test_isi_artikel_tidak_diambil_ketika_robots_txt_melarang(): void
    {
        config(['ai.key' => 'kunci-uji']);

        Sumber::factory()->create(['feed_url' => 'https://banten.antaranews.com/rss/terkini.xml']);

        Http::fake([
            'https://banten.antaranews.com/rss/*' => Http::response($this->rssFixture()),
            'https://banten.antaranews.com/robots.txt' => Http::response("User-agent: *\nDisallow: /"),
            'https://banten.antaranews.com/berita/*' => Http::response('<html><body><p>'.str_repeat('Isi. ', 100).'</p></body></html>'),
            'https://generativelanguage.googleapis.com/*' => Http::response(json_encode([
                'candidates' => [['content' => ['parts' => [['text' => 'Seharusnya tidak dipanggil.']]]]],
            ]), 200, ['Content-Type' => 'application/json']),
            '*' => Http::response('', 404),
        ]);

        app(NewsFetcher::class)->run();

        $berita = Berita::sole();

        $this->assertSame('feed', $berita->diringkas_oleh);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'generativelanguage'));
        Http::assertNotSent(fn ($request) => $request->url() === 'https://banten.antaranews.com/berita/normalisasi-pamulang');
    }

    public function test_kategorisasi_dari_kata_kunci(): void
    {
        Kategori::factory()->create(['scope' => 'berita', 'nama' => 'Kesehatan', 'slug' => 'kesehatan-2']);
        $kecamatan = Kecamatan::factory()->create(['nama' => 'Pamulang', 'slug' => 'pamulang-2']);

        Sumber::factory()->create(['feed_url' => 'https://banten.antaranews.com/rss/terkini.xml']);

        Http::fake(['*' => Http::response($this->rssFixture())]);

        app(NewsFetcher::class)->run();

        $berita = Berita::sole();

        $this->assertSame('Kesehatan', $berita->kategori->nama);
        $this->assertSame($kecamatan->id, $berita->kecamatan_id);
    }

    public function test_sumber_gagal_mencatat_status_error(): void
    {
        Sumber::factory()->create(['feed_url' => 'https://banten.antaranews.com/rss/terkini.xml']);

        Http::fake(['*' => Http::response('server error', 500)]);

        $stats = app(NewsFetcher::class)->run();

        $this->assertSame(1, $stats['gagal']);
        $this->assertSame(0, $stats['dibuat']);

        $sumber = Sumber::sole();
        $this->assertSame('error', $sumber->last_status);
        $this->assertStringContainsString('HTTP 500', $sumber->last_error);
    }

    public function test_sumber_nonaktif_dilewati_dan_filter_source_bekerja(): void
    {
        Sumber::factory()->create([
            'nama' => 'Nonaktif',
            'feed_url' => 'https://banten.antaranews.com/rss/terkini.xml',
            'aktif' => false,
        ]);
        $aktif = Sumber::factory()->create([
            'nama' => 'Sumber Aktif',
            'feed_url' => 'https://banten.antaranews.com/rss/terkini.xml',
        ]);

        Http::fake(['*' => Http::response($this->rssFixture())]);

        $stats = app(NewsFetcher::class)->run('Sumber Aktif');

        $this->assertSame(1, $stats['sumber']);
        $this->assertSame(1, $stats['dibuat']);
        $this->assertSame('ok', $aktif->fresh()->last_status);
        $this->assertNull(Sumber::where('nama', 'Nonaktif')->sole()->last_status);
    }

    private function rssFixture(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
            <channel>
            <title>ANTARA Banten</title>
            <item>
              <title>RSUD Tangsel Tambah Layanan Kesehatan di Pamulang</title>
              <link>https://banten.antaranews.com/berita/normalisasi-pamulang</link>
              <pubDate>Mon, 05 Oct 2026 08:00:00 +0700</pubDate>
              <description><![CDATA[<p>Pemerintah Kota menyelesaikan <b>normalisasi saluran</b> di Pamulang menjelang musim hujan.</p>]]></description>
              <media:thumbnail url="https://antaranews.example/img-pamulang.jpg"/>
            </item>
            </channel>
            </rss>
            XML;
    }

    private function bingFixture(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
            <channel>
            <title>Bing News</title>
            <item>
              <title>Serpong Kedatangan Pemain Baru</title>
              <link>https://www.bing.com/news/apiclick.aspx?ref=FexRss&amp;tid=abc&amp;url=https%3a%2f%2fwww.rm.id%2fbaca%2fberita%2fserpong-pemain-baru&amp;app=msn&amp;mkt=en-id</link>
              <pubDate>Mon, 05 Oct 2026 09:00:00 +0700</pubDate>
              <description>Tim Serpong merekrut pemain baru untuk musim depan.</description>
              <source url="https://rm.id">RMID</source>
            </item>
            </channel>
            </rss>
            XML;
    }
}
