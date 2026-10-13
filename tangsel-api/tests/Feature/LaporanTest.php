<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_butuh_login(): void
    {
        $this->postJson('/api/laporan', $this->payload())->assertStatus(401);
    }

    public function test_meta_form_menyediakan_kategori_laporan_dan_kecamatan(): void
    {
        Kategori::factory()->laporan()->create(['nama' => 'Jalan Rusak']);
        Kategori::factory()->create(['nama' => 'Kategori Berita']);

        $this->getJson('/api/laporan/meta')
            ->assertOk()
            ->assertJsonCount(1, 'data.kategori')
            ->assertJsonPath('data.kategori.0.nama', 'Jalan Rusak')
            ->assertJsonStructure([
                'data' => [
                    'kategori' => [
                        '*' => ['id', 'nama', 'slug', 'ikon'],
                    ],
                    'kecamatan' => [
                        '*' => ['id', 'nama', 'slug'],
                    ],
                ],
            ]);
    }

    public function test_warga_mengirim_laporan_dengan_foto(): void
    {
        $warga = User::factory()->create();

        $response = $this->actingAs($warga)
            ->post('/api/laporan', $this->payload([
                'foto' => [UploadedFile::fake()->image('foto.jpg')],
            ]), ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'baru')
            ->assertJsonPath('data.pelapor.nama', $warga->nama)
            ->assertJsonCount(1, 'data.foto');

        $this->assertStringStartsWith('LAP-', $response->json('data.kode'));

        $laporan = Laporan::where('kode', $response->json('data.kode'))->sole();
        $this->assertSame($warga->id, $laporan->pelapor_id);
        $this->assertSame(1, $laporan->foto()->count());
    }

    public function test_store_validasi_maks_tiga_foto_dan_field_wajib(): void
    {
        $warga = User::factory()->create();
        $fotos = [];
        for ($i = 0; $i < 4; $i++) {
            $fotos[] = UploadedFile::fake()->image("foto$i.jpg");
        }

        $payload = $this->payload(['foto' => $fotos]);
        unset($payload['judul'], $payload['kategori_id']);

        $this->actingAs($warga)
            ->post('/api/laporan', $payload, ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_feed_publik_hanya_menampilkan_diverifikasi_ke_atas(): void
    {
        $kategori = Kategori::factory()->laporan()->create();
        Laporan::factory()->create(['kategori_id' => $kategori->id, 'judul' => 'Laporan Rahasia Baru']);
        Laporan::factory()->create(['status' => 'ditolak', 'judul' => 'Laporan Ditolak']);
        Laporan::factory()->create(['status' => 'diverifikasi', 'judul' => 'Laporan Terverifikasi']);
        Laporan::factory()->create(['status' => 'diproses']);
        Laporan::factory()->create(['status' => 'selesai']);

        $this->getJson('/api/laporan')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['judul' => 'Laporan Terverifikasi'])
            ->assertJsonMissing(['judul' => 'Laporan Rahasia Baru'])
            ->assertJsonMissing(['judul' => 'Laporan Ditolak'])
            ->assertJsonPath('data.0.pelapor', null)
            ->assertJsonPath('data.0.catatan_admin', null);
    }

    public function test_feed_mine_memerlukan_login_dan_hanya_milik_pengguna(): void
    {
        $this->getJson('/api/laporan?mine=1')->assertStatus(401);

        $warga = User::factory()->create();
        Laporan::factory()->create(['pelapor_id' => $warga->id, 'status' => 'baru', 'judul' => 'Milik Saya']);
        Laporan::factory()->create(['status' => 'diverifikasi', 'judul' => 'Milik Orang Lain']);

        $this->actingAs($warga)
            ->getJson('/api/laporan?mine=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Milik Saya')
            ->assertJsonPath('data.0.status', 'baru');
    }

    public function test_feed_mine_menerima_bearer_token(): void
    {
        $warga = User::factory()->create();
        $token = $warga->createToken('uji')->plainTextToken;

        Laporan::factory()->create(['pelapor_id' => $warga->id, 'status' => 'baru', 'judul' => 'Milik Saya']);
        Laporan::factory()->create(['status' => 'diverifikasi', 'judul' => 'Milik Orang Lain']);

        $this->withToken($token)
            ->getJson('/api/laporan?mine=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Milik Saya');
    }

    public function test_detail_hanya_pemilik_atau_admin(): void
    {
        $pemilik = User::factory()->create();
        $orangLain = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $laporan = Laporan::factory()->create([
            'pelapor_id' => $pemilik->id,
            'status' => 'diverifikasi',
            'catatan_admin' => 'Diverifikasi. Tim berangkat ke lokasi.',
        ]);

        $this->getJson('/api/laporan/'.$laporan->kode)->assertStatus(401);

        $this->actingAs($orangLain)
            ->getJson('/api/laporan/'.$laporan->kode)
            ->assertStatus(404);

        $this->actingAs($pemilik)
            ->getJson('/api/laporan/'.$laporan->kode)
            ->assertOk()
            ->assertJsonPath('data.pelapor.nama', $pemilik->nama)
            ->assertJsonPath('data.catatan_admin', 'Diverifikasi. Tim berangkat ke lokasi.');

        $this->actingAs($admin)
            ->getJson('/api/laporan/'.$laporan->kode)
            ->assertOk();
    }

    public function test_admin_dapat_melihat_semua_status_laporan(): void
    {
        $admin = User::factory()->admin()->create();
        $warga = User::factory()->create();

        Laporan::factory()->create(['status' => 'baru', 'judul' => 'Masih Baru']);
        Laporan::factory()->create(['status' => 'ditolak', 'judul' => 'Sudah Ditolak']);

        $this->actingAs($warga)->getJson('/api/admin/laporan')->assertStatus(403);

        $this->actingAs($admin)
            ->getJson('/api/admin/laporan')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['judul' => 'Masih Baru'])
            ->assertJsonFragment(['judul' => 'Sudah Ditolak']);

        $this->actingAs($admin)
            ->getJson('/api/admin/laporan?status=baru')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Masih Baru');
    }

    public function test_verifikasi_hanya_admin_dan_di_status_baru(): void
    {
        $warga = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $laporan = Laporan::factory()->create(['pelapor_id' => $warga->id, 'status' => 'baru']);

        $this->actingAs($warga)
            ->postJson('/api/admin/laporan/'.$laporan->kode.'/verifikasi', ['status' => 'diverifikasi'])
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson('/api/admin/laporan/'.$laporan->kode.'/verifikasi', [
                'status' => 'diverifikasi',
                'catatan_admin' => 'Lokasi sudah dicek.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'diverifikasi');

        $this->assertSame('Lokasi sudah dicek.', $laporan->fresh()->catatan_admin);

        $this->actingAs($admin)
            ->postJson('/api/admin/laporan/'.$laporan->kode.'/verifikasi', ['status' => 'diverifikasi'])
            ->assertStatus(422);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/laporan/'.$laporan->kode.'/verifikasi', ['status' => 'ditolak'])
            ->assertStatus(422);

        $this->assertStringContainsString('tidak valid', $response->json('message'));
    }

    public function test_alur_status_sampai_selesai(): void
    {
        $admin = User::factory()->admin()->create();
        $laporan = Laporan::factory()->create(['status' => 'baru']);

        $this->actingAs($admin)
            ->postJson('/api/admin/laporan/'.$laporan->kode.'/status', ['status' => 'diproses'])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->postJson('/api/admin/laporan/'.$laporan->kode.'/verifikasi', ['status' => 'diverifikasi'])
            ->assertOk();

        $this->actingAs($admin)
            ->postJson('/api/admin/laporan/'.$laporan->kode.'/status', ['status' => 'diproses'])
            ->assertOk()
            ->assertJsonPath('data.status', 'diproses');

        $this->actingAs($admin)
            ->postJson('/api/admin/laporan/'.$laporan->kode.'/status', ['status' => 'selesai'])
            ->assertOk();

        $selesai = $laporan->fresh();
        $this->assertSame('selesai', $selesai->status);
        $this->assertNotNull($selesai->diselesaikan_at);

        $this->getJson('/api/laporan')->assertJsonFragment(['kode' => $laporan->kode]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'kategori_id' => Kategori::factory()->laporan()->create()->id,
            'kecamatan_id' => Kecamatan::factory()->create()->id,
            'judul' => 'Jalan Berlubang di Dekat Sekolah',
            'deskripsi' => 'Jalan berlubang cukup dalam di depan sekolah dasar, membahayakan pengendara motor saat hujan. Sudah terjadi sejak dua minggu terakhir.',
            'latitude' => -6.2867,
            'longitude' => 106.7181,
        ], $overrides);
    }
}
