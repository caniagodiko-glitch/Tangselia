<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\LayananKesehatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KesehatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_kesehatan_dan_filter_jenis(): void
    {
        LayananKesehatan::factory()->count(2)->create(['jenis' => 'rumah_sakit']);
        LayananKesehatan::factory()->create(['jenis' => 'apotek']);

        $this->getJson('/api/kesehatan')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/kesehatan?jenis=apotek')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_list_kesehatan_difilter_kecamatan_dan_dicari(): void
    {
        $kecamatan = Kecamatan::factory()->create(['slug' => 'pondok-aren']);
        LayananKesehatan::factory()->create([
            'nama' => 'RSUD Pondok Aren Sehat',
            'kecamatan_id' => $kecamatan->id,
        ]);
        LayananKesehatan::factory()->create(['nama' => 'Puskesmas Ciputat']);

        $this->getJson('/api/kesehatan?kecamatan=pondok-aren')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/kesehatan?q=Pondok Aren Sehat')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_detail_aktif_200_dan_tidak_ada_404(): void
    {
        $layanan = LayananKesehatan::factory()->create(['status' => 'aktif']);

        $this->getJson('/api/kesehatan/'.$layanan->slug)
            ->assertOk()
            ->assertJsonPath('data.nama', $layanan->nama)
            ->assertJsonStructure(['data' => ['telepon', 'layanan_24_jam', 'apotek', 'kecamatan']]);

        $this->getJson('/api/kesehatan/tidak-ada')->assertNotFound();
    }

    public function test_meta_kesehatan_mengembalikan_jumlah(): void
    {
        LayananKesehatan::factory()->count(3)->create(['jenis' => 'puskesmas']);

        $this->getJson('/api/kesehatan/meta')
            ->assertOk()
            ->assertJsonPath('data.jenis.0.value', 'apotek')
            ->assertJsonPath('data.jenis.2.jumlah', 3);
    }

    public function test_crud_admin_terproteksi_role(): void
    {
        $warga = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $payload = [
            'kecamatan_id' => Kecamatan::factory()->create()->id,
            'jenis' => 'klinik',
            'nama' => 'Klinik Baru',
            'alamat' => 'Jl. Sehat No. 3',
            'latitude' => -6.2800000,
            'longitude' => 106.7400000,
            'telepon' => '(021) 7555-000',
            'layanan_24_jam' => false,
        ];

        $this->postJson('/api/admin/kesehatan', $payload)->assertStatus(401);
        $this->actingAs($warga)->postJson('/api/admin/kesehatan', $payload)->assertStatus(403);

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/kesehatan', $payload)
            ->assertStatus(201);

        $id = $created->json('data.id');

        $this->actingAs($admin)
            ->putJson('/api/admin/kesehatan/'.$id, array_merge($payload, ['nama' => 'Klinik Diubah']))
            ->assertOk()
            ->assertJsonPath('data.nama', 'Klinik Diubah');

        $this->actingAs($admin)
            ->deleteJson('/api/admin/kesehatan/'.$id)
            ->assertOk();

        $this->assertDatabaseMissing('layanan_kesehatan', ['id' => $id]);
    }
}
