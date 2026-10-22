<?php

namespace Tests\Feature;

use App\Models\Lowongan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowonganAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_kurasi_membutuhkan_login_dan_role_admin(): void
    {
        $this->getJson('/api/admin/lowongan')->assertStatus(401);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/admin/lowongan')
            ->assertStatus(403);
    }

    public function test_permintaan_tanpa_header_accept_tetap_menghasilkan_401_json(): void
    {
        // Tanpa header Accept, middleware bawaan Laravel mencoba
        // mengalihkan tamu ke rute "login" yang tidak ada sehingga
        // berakhir sebagai galat 500.
        $this->get('/api/admin/lowongan')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_admin_melihat_semua_status_bisa_disaring(): void
    {
        $admin = User::factory()->admin()->create();

        Lowongan::factory()->create(['status' => 'baru']);
        Lowongan::factory()->tayang()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/lowongan')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin)
            ->getJson('/api/admin/lowongan?status=baru')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_menerbitkan_lowongan_baru(): void
    {
        $admin = User::factory()->admin()->create();
        $lowongan = Lowongan::factory()->create(['status' => 'baru']);

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/lowongan/'.$lowongan->id, ['status' => 'tayang'])
            ->assertOk()
            ->assertJsonPath('data.status', 'tayang');

        $this->assertNotNull($response->json('data.tayang_at'));
        $this->assertNotNull($response->json('data.published_at'));
        $this->assertNotNull($response->json('data.kedaluwarsa_at'));
    }

    public function test_admin_menolak_lowongan_dengan_catatan(): void
    {
        $admin = User::factory()->admin()->create();
        $lowongan = Lowongan::factory()->create(['status' => 'baru']);

        $this->actingAs($admin)
            ->putJson('/api/admin/lowongan/'.$lowongan->id, [
                'status' => 'ditolak',
                'catatan_admin' => 'Posisi sudah penuh.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'ditolak')
            ->assertJsonPath('data.catatan_admin', 'Posisi sudah penuh.');
    }

    public function test_transisi_status_tidak_sah_ditolak(): void
    {
        $admin = User::factory()->admin()->create();
        $lowongan = Lowongan::factory()->create(['status' => 'baru']);

        $this->actingAs($admin)
            ->putJson('/api/admin/lowongan/'.$lowongan->id, ['status' => 'kedaluwarsa'])
            ->assertStatus(422);
    }

    public function test_admin_memperbaiki_hasil_ekstraksi(): void
    {
        $admin = User::factory()->admin()->create();
        $lowongan = Lowongan::factory()->create(['status' => 'baru']);

        $this->actingAs($admin)
            ->putJson('/api/admin/lowongan/'.$lowongan->id, [
                'posisi' => 'Front Office',
                'perusahaan' => 'PT Grand Indonesia',
                'jenis' => 'part-time',
                'deskripsi' => 'Melayani tamu hotel pada shift malam.',
            ])
            ->assertOk()
            ->assertJsonPath('data.posisi', 'Front Office')
            ->assertJsonPath('data.perusahaan', 'PT Grand Indonesia')
            ->assertJsonPath('data.jenis', 'part-time');
    }

    public function test_admin_menghapus_lowongan(): void
    {
        $admin = User::factory()->admin()->create();
        $lowongan = Lowongan::factory()->create();

        $this->actingAs($admin)
            ->deleteJson('/api/admin/lowongan/'.$lowongan->id)
            ->assertOk();

        $this->assertDatabaseMissing('lowongan', ['id' => $lowongan->id]);
    }

    public function test_perintah_expire_menandai_lowongan_kedaluwarsa(): void
    {
        Lowongan::factory()->tayang()->create([
            'kedaluwarsa_at' => now()->subDay(),
        ]);

        Lowongan::factory()->tayang()->create([
            'kedaluwarsa_at' => now()->addDays(10),
        ]);

        $this->artisan('lowongan:expire')->assertSuccessful();

        $this->assertSame(1, Lowongan::where('status', 'kedaluwarsa')->count());
        $this->assertSame(1, Lowongan::where('status', 'tayang')->count());
    }
}
