<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeritaPublikTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_berita_hanya_menampilkan_yang_terbit(): void
    {
        Berita::factory()->count(2)->create();
        Berita::factory()->create(['status' => 'draft']);

        $this->getJson('/api/berita')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_list_berita_difilter_kategori_kecamatan_tipe_dan_pencarian(): void
    {
        $kategori = Kategori::factory()->create(['slug' => 'transportasi']);
        $kecamatan = Kecamatan::factory()->create(['slug' => 'pamulang']);

        Berita::factory()->create([
            'judul' => 'Kemacetan Pamulang Memburuk',
            'kategori_id' => $kategori->id,
            'kecamatan_id' => $kecamatan->id,
        ]);
        Berita::factory()->agregat()->create(['judul' => 'Berita Agregat Terpisah']);
        Berita::factory()->create(['judul' => 'Berita Lainnya']);

        $this->getJson('/api/berita?kategori=transportasi')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/berita?kecamatan=pamulang')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/berita?tipe=agregat')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/berita?q=Kemacetan')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_list_berita_difilter_sumber(): void
    {
        Berita::factory()->agregat()->create(['sumber' => 'ANTARA News Banten']);
        Berita::factory()->agregat()->create(['sumber' => 'PosBanten']);

        $this->getJson('/api/berita?sumber='.urlencode('PosBanten'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_terbaru_mengembalikan_urutan_dan_dibatasi_limit(): void
    {
        Berita::factory()->create(['judul' => 'Berita Lama', 'published_at' => now()->subDay()]);
        Berita::factory()->create(['judul' => 'Berita Baru', 'published_at' => now()]);

        $this->getJson('/api/berita/terbaru?limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Berita Baru');

        $this->getJson('/api/berita/terbaru?limit=99')->assertStatus(422);
    }

    public function test_detail_berita_aktif_200_dan_tidak_ada_404(): void
    {
        $berita = Berita::factory()->create([
            'slug' => 'berita-aktif',
            'status' => 'published',
        ]);

        $this->getJson('/api/berita/berita-aktif')
            ->assertOk()
            ->assertJsonPath('data.judul', $berita->judul)
            ->assertJsonStructure([
                'data' => ['tipe', 'ringkasan', 'diringkas_oleh', 'kategori', 'kecamatan', 'published_at'],
            ]);

        $this->getJson('/api/berita/'.Berita::factory()->create(['status' => 'draft'])->slug)
            ->assertNotFound();

        $this->getJson('/api/berita/tidak-ada')->assertNotFound();
    }

    public function test_meta_mengembalikan_kategori_tipe_dan_sumber(): void
    {
        $kategori = Kategori::factory()->create([
            'scope' => 'berita',
            'nama' => 'Kesehatan',
            'slug' => 'kesehatan-meta',
        ]);

        Berita::factory()->count(2)->create(['kategori_id' => $kategori->id]);
        Berita::factory()->agregat()->create(['sumber' => 'PosBanten']);

        $response = $this->getJson('/api/berita/meta')->assertOk();

        $kategoriMeta = collect($response->json('data.kategori'))
            ->firstWhere('slug', 'kesehatan-meta');
        $this->assertSame(2, $kategoriMeta['jumlah']);

        $this->assertSame(['editorial', 'agregat'], array_column($response->json('data.tipe'), 'value'));
        $this->assertSame('PosBanten', $response->json('data.sumber.0.nama'));
    }
}
