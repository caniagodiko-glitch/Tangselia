<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Sumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BeritaAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_editorial_butuh_login_dan_role_admin(): void
    {
        $payload = $this->payload();

        $this->postJson('/api/admin/berita', $payload)->assertStatus(401);
        $this->actingAs(User::factory()->create())
            ->postJson('/api/admin/berita', $payload)
            ->assertStatus(403);
    }

    public function test_admin_membuat_editorial_terbit(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/berita', $this->payload())
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'judul', 'slug', 'tipe', 'status', 'kategori']]);

        $this->assertDatabaseHas('berita', [
            'id' => $response->json('data.id'),
            'tipe' => 'editorial',
            'status' => 'published',
            'editor_id' => $admin->id,
            'diringkas_oleh' => 'editor',
            'slug' => 'judul-editorial-baru',
        ]);

        $this->assertNotNull($response->json('data.published_at'));
    }

    public function test_store_editorial_bawaan_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->payload();
        unset($payload['status']);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/berita', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('berita', [
            'id' => $response->json('data.id'),
            'status' => 'draft',
        ]);
        $this->assertNull($response->json('data.published_at'));
    }

    public function test_admin_mengunggah_gambar_editorial(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post('/api/admin/berita', $this->payload([
                'gambar' => UploadedFile::fake()->image('cover.jpg'),
            ]), ['Accept' => 'application/json'])
            ->assertStatus(201);

        $this->assertStringContainsString('/storage/berita/', $response->json('data.gambar'));
        $this->assertStringStartsWith('berita/', Berita::find($response->json('data.id'))->gambar);
    }

    public function test_admin_mengubah_editorial_tanpa_mengubah_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $berita = Berita::factory()->create([
            'tipe' => 'editorial',
            'editor_id' => $admin->id,
            'status' => 'draft',
            'slug' => 'slug-awal-tetap',
        ]);

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/berita/'.$berita->id, $this->payload([
                'judul' => 'Judul Editorial Baru',
                'status' => 'published',
            ]))
            ->assertOk()
            ->assertJsonPath('data.slug', 'slug-awal-tetap')
            ->assertJsonPath('data.status', 'published');

        $this->assertNotNull($berita->fresh()->published_at);
    }

    public function test_update_editorial_parsial_tidak_mengubah_status(): void
    {
        $admin = User::factory()->admin()->create();
        $berita = Berita::factory()->create([
            'tipe' => 'editorial',
            'editor_id' => $admin->id,
            'status' => 'draft',
        ]);

        $payload = $this->payload();
        unset($payload['status']);

        $this->actingAs($admin)
            ->putJson('/api/admin/berita/'.$berita->id, $payload)
            ->assertOk();

        $this->assertSame('draft', $berita->fresh()->status);
    }

    public function test_berita_agregat_tidak_bisa_diubah_atau_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $agregat = Berita::factory()->agregat()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/berita/'.$agregat->id, $this->payload())
            ->assertStatus(403);

        $this->actingAs($admin)
            ->deleteJson('/api/admin/berita/'.$agregat->id)
            ->assertStatus(403);
    }

    public function test_admin_menghapus_editorial(): void
    {
        $admin = User::factory()->admin()->create();
        $berita = Berita::factory()->create([
            'tipe' => 'editorial',
            'editor_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson('/api/admin/berita/'.$berita->id)
            ->assertOk();

        $this->assertDatabaseMissing('berita', ['id' => $berita->id]);
    }

    public function test_refresh_membutuhkan_admin_dan_mengembalikan_statistik(): void
    {
        $admin = User::factory()->admin()->create();
        Kategori::factory()->create([
            'scope' => 'berita',
            'nama' => 'Berita Kota',
            'slug' => 'berita-kota',
            'urutan' => 1,
        ]);
        Sumber::factory()->create([
            'nama' => 'Sumber Uji',
            'feed_url' => 'https://banten.antaranews.com/rss/terkini.xml',
        ]);

        Http::fake([
            'https://banten.antaranews.com/rss/*' => Http::response($this->rssFixture()),
            '*' => Http::response('', 404),
        ]);

        $this->postJson('/api/admin/berita/refresh')->assertStatus(401);
        $this->actingAs(User::factory()->create())
            ->postJson('/api/admin/berita/refresh')
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson('/api/admin/berita/refresh')
            ->assertOk()
            ->assertJsonPath('data.sumber', 1)
            ->assertJsonPath('data.dibuat', 1);

        $this->assertDatabaseHas('berita', ['tipe' => 'agregat', 'status' => 'published']);
    }

    public function test_daftar_sumber_untuk_pemantauan(): void
    {
        $admin = User::factory()->admin()->create();
        Sumber::factory()->create(['nama' => 'ANTARA News Banten', 'last_status' => 'ok']);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/admin/berita/sumber')
            ->assertStatus(403);

        $this->actingAs($admin)
            ->getJson('/api/admin/berita/sumber')
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'ANTARA News Banten')
            ->assertJsonPath('data.0.last_status', 'ok');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'judul' => 'Judul Editorial Baru',
            'konten' => "Paragraf pembuka berita editorial.\n\n".str_repeat('Isi paragraf yang menjelaskan peristiwa secara rinci. ', 10),
            'kategori_id' => Kategori::factory()->create()->id,
            'kecamatan_id' => Kecamatan::factory()->create()->id,
            'status' => 'published',
        ], $overrides);
    }

    private function rssFixture(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
            <channel>
            <title>ANTARA Banten</title>
            <item>
              <title>Duabelas Judul Baru Dari Sumber Uji</title>
              <link>https://banten.antaranews.com/berita/satu</link>
              <pubDate>Mon, 05 Oct 2026 08:00:00 +0700</pubDate>
              <description>Ringkasan singkat berita dari feed.</description>
            </item>
            </channel>
            </rss>
            XML;
    }
}
