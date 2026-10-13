<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Tempat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KulinerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_kuliner_mengembalikan_data_terpaginasi(): void
    {
        Tempat::factory()->count(3)->create();

        $this->getJson('/api/kuliner')
            ->assertOk()
            ->assertJsonStructure(['data', 'links' => ['next', 'prev']])
            ->assertJsonCount(3, 'data');
    }

    public function test_list_bisa_difilter_jenis_dan_kecamatan(): void
    {
        $kecamatan = Kecamatan::factory()->create(['slug' => 'pamulang']);
        Tempat::factory()->create(['jenis' => 'kafe', 'kecamatan_id' => $kecamatan->id]);
        Tempat::factory()->create(['jenis' => 'warkop']);

        $this->getJson('/api/kuliner?jenis=kafe')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/kuliner?kecamatan=pamulang')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/kuliner?jenis=restoran')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_list_bisa_dicari_dengan_q(): void
    {
        Tempat::factory()->create(['nama' => 'Kedai Kopi Senja']);
        Tempat::factory()->create(['nama' => 'Warung Makan Bude']);

        $this->getJson('/api/kuliner?q=Senja')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_cursor_ke_halaman_berikutnya_jalan(): void
    {
        Tempat::factory()->count(3)->create();

        $first = $this->getJson('/api/kuliner?per_page=2')->assertOk();
        $next = $first->json('links.next');
        $this->assertNotNull($next);

        $this->getJson($next)->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_detail_untuk_slug_aktif_dan_404(): void
    {
        $aktif = Tempat::factory()->create(['status' => 'aktif']);
        $this->getJson('/api/kuliner/'.$aktif->slug)
            ->assertOk()
            ->assertJsonPath('data.nama', $aktif->nama)
            ->assertJsonStructure(['data' => ['kecamatan' => ['nama', 'slug']]]);

        $nonaktif = Tempat::factory()->create(['status' => 'nonaktif']);
        $this->getJson('/api/kuliner/'.$nonaktif->slug)->assertNotFound();
        $this->getJson('/api/kuliner/tidak-ada')->assertNotFound();
    }

    public function test_meta_mengembalikan_jumlah_jenis_dan_kecamatan(): void
    {
        Tempat::factory()->count(2)->create(['jenis' => 'kafe']);

        $this->getJson('/api/kuliner/meta')
            ->assertOk()
            ->assertJsonStructure(['data' => ['jenis', 'kecamatan']])
            ->assertJsonPath('data.jenis.0.value', 'kafe')
            ->assertJsonPath('data.jenis.0.jumlah', 2);
    }

    public function test_store_tanpa_token_401_dan_warga_403(): void
    {
        $payload = $this->payload();

        $this->postJson('/api/admin/tempat', $payload)->assertStatus(401);

        $warga = User::factory()->create();
        $this->actingAs($warga)->postJson('/api/admin/tempat', $payload)->assertStatus(403);
    }

    public function test_store_sebagai_admin_201_dan_masuk_database(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/tempat', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.jenis', 'kafe');

        $this->assertDatabaseHas('tempat', ['slug' => 'kafe-baru']);
    }

    public function test_store_dengan_data_tidak_lengkap_422(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/tempat', ['nama' => 'Kafe Baru'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['kecamatan_id', 'jenis', 'alamat', 'latitude', 'longitude']);
    }

    public function test_update_dan_destroy_hanya_admin(): void
    {
        $tempat = Tempat::factory()->create();
        $warga = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($warga)
            ->putJson('/api/admin/tempat/'.$tempat->id, ['nama' => 'Nama Baru'])
            ->assertStatus(403);

        $this->actingAs($admin)
            ->putJson('/api/admin/tempat/'.$tempat->id, [
                'kecamatan_id' => $tempat->kecamatan_id,
                'jenis' => $tempat->jenis,
                'nama' => 'Nama Baru',
                'alamat' => $tempat->alamat,
                'latitude' => $tempat->latitude,
                'longitude' => $tempat->longitude,
            ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Nama Baru');

        $this->actingAs($admin)
            ->deleteJson('/api/admin/tempat/'.$tempat->id)
            ->assertOk();

        $this->assertDatabaseMissing('tempat', ['id' => $tempat->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'kecamatan_id' => Kecamatan::factory()->create()->id,
            'jenis' => 'kafe',
            'nama' => 'Kafe Baru',
            'alamat' => 'Jl. Baru No. 1',
            'latitude' => -6.3000000,
            'longitude' => 106.7000000,
            'jam_buka' => '08:00',
            'jam_tutup' => '22:00',
            'rentang_harga' => '< Rp25.000',
        ];
    }
}
