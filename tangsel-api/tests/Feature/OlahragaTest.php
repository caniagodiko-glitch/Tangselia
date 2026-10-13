<?php

namespace Tests\Feature;

use App\Models\FasilitasOlahraga;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OlahragaTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_olahraga_dan_filter_jenis(): void
    {
        FasilitasOlahraga::factory()->count(2)->create(['jenis' => 'lapangan_futsal']);
        FasilitasOlahraga::factory()->create(['jenis' => 'gym']);

        $this->getJson('/api/olahraga')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/olahraga?jenis=gym')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_list_olahraga_difilter_kecamatan_dan_dicari(): void
    {
        $kecamatan = Kecamatan::factory()->create(['slug' => 'serpong']);
        FasilitasOlahraga::factory()->create([
            'nama' => 'GOR Serpong Jaya',
            'kecamatan_id' => $kecamatan->id,
        ]);
        FasilitasOlahraga::factory()->create(['nama' => 'Kolam Renang Ciputat']);

        $this->getJson('/api/olahraga?kecamatan=serpong')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/olahraga?q=Serpong Jaya')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_detail_aktif_200_dan_tidak_ada_404(): void
    {
        $fasilitas = FasilitasOlahraga::factory()->create(['status' => 'aktif']);

        $this->getJson('/api/olahraga/'.$fasilitas->slug)
            ->assertOk()
            ->assertJsonPath('data.nama', $fasilitas->nama)
            ->assertJsonStructure(['data' => ['kapasitas', 'tarif_sewa', 'kecamatan']]);

        $this->getJson('/api/olahraga/tidak-ada')->assertNotFound();
    }

    public function test_meta_olahraga_mengembalikan_jumlah(): void
    {
        FasilitasOlahraga::factory()->count(2)->create(['jenis' => 'gor_bulutangkis']);

        $this->getJson('/api/olahraga/meta')
            ->assertOk()
            ->assertJsonPath('data.jenis.0.value', 'gor_bulutangkis')
            ->assertJsonPath('data.jenis.0.jumlah', 2);
    }

    public function test_crud_admin_terproteksi_role(): void
    {
        $warga = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $payload = [
            'kecamatan_id' => Kecamatan::factory()->create()->id,
            'jenis' => 'lapangan_futsal',
            'nama' => 'Lapangan Futsal Baru',
            'alamat' => 'Jl. Olahraga No. 9',
            'latitude' => -6.3100000,
            'longitude' => 106.7200000,
            'kapasitas' => 20,
            'tarif_sewa' => 'Rp40.000/jam',
        ];

        $this->postJson('/api/admin/olahraga', $payload)->assertStatus(401);
        $this->actingAs($warga)->postJson('/api/admin/olahraga', $payload)->assertStatus(403);

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/olahraga', $payload)
            ->assertStatus(201);

        $id = $created->json('data.id');

        $this->actingAs($admin)
            ->putJson('/api/admin/olahraga/'.$id, array_merge($payload, ['nama' => 'Futsal Baru Diubah']))
            ->assertOk()
            ->assertJsonPath('data.nama', 'Futsal Baru Diubah');

        $this->actingAs($admin)
            ->deleteJson('/api/admin/olahraga/'.$id)
            ->assertOk();

        $this->assertDatabaseMissing('fasilitas_olahraga', ['id' => $id]);
    }
}
