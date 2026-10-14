<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Tempat;
use App\Services\OsmImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PengayaanOsmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Kecamatan::factory()->create(['nama' => 'Pamulang', 'slug' => 'pamulang']);
    }

    public function test_impor_pertama_mengisi_website_telepon_dan_atribusi_odbl(): void
    {
        Http::fake(['*' => Http::response(['elements' => [
            ['type' => 'node', 'id' => 501, 'lat' => -6.3439, 'lon' => 106.7398, 'tags' => [
                'name' => 'Kafe Kaya',
                'amenity' => 'cafe',
                'website' => 'kafekaya.id',
                'phone' => '+62 21 555',
            ]],
        ]])]);

        app(OsmImporter::class)->import('tempat');

        $kafe = Tempat::where('osm_id', 'node/501')->sole();

        $this->assertSame('https://kafekaya.id', $kafe->website);
        $this->assertSame('+62 21 555', $kafe->telepon);
        $this->assertSame('osm', $kafe->deskripsi_sumber);
        $this->assertContains('OpenStreetMap', array_column($kafe->atribusi, 'sumber'));
    }

    public function test_impor_ulang_hanya_mengisi_kolom_kosong_dan_tidak_menimpa_suntingan(): void
    {
        Http::fakeSequence()
            ->push(['elements' => [
                ['type' => 'node', 'id' => 502, 'lat' => -6.3439, 'lon' => 106.7398, 'tags' => [
                    'name' => 'Kafe Sunting',
                    'amenity' => 'cafe',
                ]],
            ]])
            ->push(['elements' => [
                ['type' => 'node', 'id' => 502, 'lat' => -6.3439, 'lon' => 106.7398, 'tags' => [
                    'name' => 'Kafe Sunting',
                    'amenity' => 'cafe',
                    'website' => 'kafesunting.id',
                    'phone' => '+62 21 111',
                ]],
            ]]);

        app(OsmImporter::class)->import('tempat');

        $kafe = Tempat::where('osm_id', 'node/502')->sole();
        $this->assertNull($kafe->website);

        $kafe->update([
            'deskripsi' => 'Deskripsi suntingan admin.',
            'website' => 'https://punya-sendiri.id',
        ]);

        $stats = app(OsmImporter::class)->import('tempat');
        $kafe->refresh();

        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame(1, $stats['diperkaya']);
        $this->assertSame('https://punya-sendiri.id', $kafe->website);
        $this->assertSame('+62 21 111', $kafe->telepon);
        $this->assertSame('Deskripsi suntingan admin.', $kafe->deskripsi);
        $this->assertContains('OpenStreetMap', array_column($kafe->atribusi, 'sumber'));
    }
}
