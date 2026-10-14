<?php

namespace Tests\Feature;

use App\Models\Tempat;
use App\Services\InfoDirektoriFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InfoDirektoriFetcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['osm.jeda_ms' => 0]);
    }

    public function test_menghormati_robots_txt_yang_melarang(): void
    {
        $tempat = Tempat::factory()->create(['slug' => 'kafe-uji', 'website' => 'https://kafeuji.id']);

        Http::fake([
            'https://kafeuji.id/robots.txt' => Http::response("User-agent: *\nDisallow: /"),
            '*' => Http::response('<html><head></head><body></body></html>'),
        ]);

        $stats = app(InfoDirektoriFetcher::class)->jalankan('tempat');

        $this->assertSame(1, $stats['dilewati']);
        $this->assertSame(0, $stats['foto']);
        $this->assertNotNull($tempat->fresh()->info_diunduh_pada);
    }

    public function test_menyimpan_foto_deskripsi_dan_menu_dari_situs_resmi(): void
    {
        Storage::fake('public');

        $tempat = Tempat::factory()->create([
            'slug' => 'kafe-enak',
            'website' => 'https://kafeenak.id',
            'deskripsi' => 'Deskripsi template OSM.',
            'deskripsi_sumber' => 'osm',
            'menu' => null,
        ]);

        $gambar = str_repeat('a', 2048);

        Http::fake([
            'https://kafeenak.id/robots.txt' => Http::response('User-agent: *'),
            'https://kafeenak.id/menu' => Http::response(
                '<html><body><ul><li>Kopi Susu</li><li>Nasi Goreng</li><li>Es Teh Manis</li></ul></body></html>'
            ),
            'https://kafeenak.id/img/cover.jpg' => Http::response($gambar, 200, ['Content-Type' => 'image/jpeg']),
            'https://kafeenak.id' => Http::response(
                '<html><head>'
                .'<meta name="description" content="Kafe enak di Pamulang dengan aneka kopi dan makanan rumahan.">'
                .'<meta property="og:image" content="/img/cover.jpg">'
                .'</head><body><a href="/menu">Menu</a></body></html>'
            ),
        ]);

        $stats = app(InfoDirektoriFetcher::class)->jalankan('tempat');
        $tempat->refresh();

        $this->assertSame(1, $stats['foto']);
        $this->assertSame(1, $stats['deskripsi']);
        $this->assertSame(1, $stats['menu']);
        $this->assertSame('situs/kafe-enak/cover.jpg', $tempat->gambar);
        Storage::disk('public')->assertExists('situs/kafe-enak/cover.jpg');
        $this->assertSame('website', $tempat->deskripsi_sumber);
        $this->assertStringContainsString('Kafe enak di Pamulang', (string) $tempat->deskripsi);
        $this->assertCount(3, $tempat->menu);
        $this->assertSame('Kopi Susu', $tempat->menu[0]['nama']);
        $this->assertContains('foto', array_column($tempat->atribusi, 'jenis'));
    }

    public function test_melewati_situs_media_sosial_tanpa_mengunduh(): void
    {
        Http::fake();

        $tempat = Tempat::factory()->create(['website' => 'https://instagram.com/sebuah-kafe']);

        $stats = app(InfoDirektoriFetcher::class)->jalankan('tempat');

        $this->assertSame(1, $stats['diperiksa']);
        $this->assertSame(1, $stats['dilewati']);
        $this->assertNotNull($tempat->fresh()->info_diunduh_pada);
        Http::assertNothingSent();
    }
}
