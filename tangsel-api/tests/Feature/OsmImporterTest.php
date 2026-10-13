<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\LayananKesehatan;
use App\Models\Tempat;
use App\Models\User;
use App\Services\OsmImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsmImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Kecamatan::factory()->create(['nama' => 'Pamulang', 'slug' => 'pamulang']);
    }

    public function test_impor_kuliner_membuat_data_dari_osm(): void
    {
        Http::fake(['*' => Http::response(['elements' => $this->elemenKuliner()])]);

        $stats = app(OsmImporter::class)->import('tempat');

        $this->assertSame(2, $stats['dibuat']);
        $this->assertSame(1, $stats['dilewati']);

        $kafe = Tempat::where('osm_id', 'node/101')->sole();

        $this->assertSame('kafe', $kafe->jenis);
        $this->assertSame('osm', $kafe->sumber);
        $this->assertSame('Pamulang', $kafe->kecamatan->nama);
        $this->assertStringContainsString('Kafe Uji', (string) $kafe->deskripsi);
        $this->assertContains('Wi-Fi', $kafe->fasilitas);
        $this->assertStringStartsWith('08:00', (string) $kafe->jam_buka);
        $this->assertSame('Mo-Su 08:00-22:00', $kafe->jam_buka_teks);
    }

    public function test_impor_ulang_tidak_menimpa_deskripsi_yang_disunting(): void
    {
        Http::fake(['*' => Http::response(['elements' => $this->elemenKuliner()])]);

        app(OsmImporter::class)->import('tempat');

        $kafe = Tempat::where('osm_id', 'node/101')->sole();
        $kafe->update(['deskripsi' => 'Deskripsi suntingan admin.']);

        $stats = app(OsmImporter::class)->import('tempat');

        $this->assertSame(0, $stats['dibuat']);
        $this->assertSame('Deskripsi suntingan admin.', $kafe->fresh()->deskripsi);
        $this->assertSame(2, Tempat::count());
    }

    public function test_impor_kesehatan_menerapkan_jenis_puskesmas(): void
    {
        Http::fake(['*' => Http::response(['elements' => [
            ['type' => 'node', 'id' => 201, 'lat' => -6.3439, 'lon' => 106.7398, 'tags' => [
                'name' => 'Puskesmas Pamulang',
                'amenity' => 'clinic',
                'opening_hours' => '24/7',
                'phone' => '+62 21 1234567',
            ]],
        ]])]);

        app(OsmImporter::class)->import('kesehatan');

        $layanan = LayananKesehatan::sole();

        $this->assertSame('puskesmas', $layanan->jenis);
        $this->assertTrue($layanan->layanan_24_jam);
        $this->assertSame('+62 21 1234567', $layanan->telepon);
    }

    public function test_endpoint_impor_hanya_admin(): void
    {
        Http::fake(['*' => Http::response(['elements' => []])]);

        $this->postJson('/api/admin/tempat/import')->assertStatus(401);

        $warga = User::factory()->create();
        $this->actingAs($warga)->postJson('/api/admin/tempat/import')->assertStatus(403);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->postJson('/api/admin/tempat/import')
            ->assertOk()
            ->assertJsonStructure(['data' => ['dibuat', 'dilewati', 'gagal']]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function elemenKuliner(): array
    {
        return [
            ['type' => 'node', 'id' => 101, 'lat' => -6.3439, 'lon' => 106.7398, 'tags' => [
                'name' => 'Kafe Uji',
                'amenity' => 'cafe',
                'opening_hours' => 'Mo-Su 08:00-22:00',
                'internet_access' => 'wlan',
                'addr:street' => 'Jl. Uji',
            ]],
            ['type' => 'node', 'id' => 102, 'lat' => -6.3439, 'lon' => 106.7398, 'tags' => ['amenity' => 'restaurant']],
            ['type' => 'node', 'id' => 103, 'lat' => -6.3439, 'lon' => 106.7398, 'tags' => ['name' => 'Warung Uji', 'amenity' => 'fast_food']],
        ];
    }
}
