<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Lowongan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowonganTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_lowongan_hanya_menampilkan_yang_tayang(): void
    {
        Lowongan::factory()->tayang()->create(['judul' => 'Lowongan tayang']);
        Lowongan::factory()->create(['judul' => 'Lowongan baru', 'status' => 'baru']);
        Lowongan::factory()->ditolak()->create(['judul' => 'Lowongan ditolak']);

        $response = $this->getJson('/api/lowongan')->assertOk();

        $judul = array_column($response->json('data'), 'judul');

        $this->assertSame(['Lowongan tayang'], $judul);
    }

    public function test_lowongan_kedaluwarsa_tidak_ditampilkan(): void
    {
        Lowongan::factory()->tayang()->create([
            'judul' => 'Lowongan kedaluwarsa',
            'kedaluwarsa_at' => now()->subDay(),
        ]);

        $this->getJson('/api/lowongan')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_daftar_lowongan_dapat_difilter_jenis_kategori_dan_kecamatan(): void
    {
        $kategori = Kategori::factory()->lowongan()->create(['nama' => 'Teknologi dan Informasi']);
        $kecamatan = Kecamatan::factory()->create(['nama' => 'Bintaro']);

        Lowongan::factory()->tayang()->create([
            'judul' => 'Lowongan magang',
            'jenis' => 'magang',
            'kategori_id' => $kategori->id,
            'kecamatan_id' => $kecamatan->id,
        ]);

        Lowongan::factory()->tayang()->create([
            'judul' => 'Lowongan shore',
            'jenis' => 'karir',
            'kategori_id' => $kategori->id,
        ]);

        $this->getJson('/api/lowongan?jenis=magang')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Lowongan magang');

        $this->getJson('/api/lowongan?kategori='.$kategori->slug)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/lowongan?kecamatan='.$kecamatan->slug)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Lowongan magang');
    }

    public function test_pencarian_lowongan_mencocokkan_posisi_dan_perusahaan(): void
    {
        Lowongan::factory()->tayang()->create([
            'judul' => 'Front Office Hotel',
            'posisi' => 'Front Office',
            'perusahaan' => 'PT Grand Indonesia',
        ]);

        Lowongan::factory()->tayang()->create([
            'judul' => 'Barista Kopi',
            'posisi' => 'Barista',
            'perusahaan' => 'Kopi Randu',
        ]);

        $this->getJson('/api/lowongan?q=barista')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.posisi', 'Barista');

        $this->getJson('/api/lowongan?q=grand')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.perusahaan', 'PT Grand Indonesia');
    }

    public function test_detail_lowongan_hanya_bisa_diakses_saat_tayang(): void
    {
        $tayang = Lowongan::factory()->tayang()->create(['slug' => 'lowongan-tayang']);

        $this->getJson('/api/lowongan/lowongan-tayang')
            ->assertOk()
            ->assertJsonPath('data.slug', 'lowongan-tayang');

        $baru = Lowongan::factory()->create(['slug' => 'lowongan-baru']);

        $this->getJson('/api/lowongan/lowongan-baru')->assertNotFound();
    }

    public function test_meta_lowongan_menyediakan_filter_dan_jumlah(): void
    {
        $kategori = Kategori::factory()->lowongan()->create(['nama' => 'Kuliner dan Perhotelan']);

        Lowongan::factory()->tayang()->create([
            'jenis' => 'karir',
            'kategori_id' => $kategori->id,
        ]);

        $this->getJson('/api/lowongan/meta')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'kategori' => [['nama', 'slug', 'jumlah']],
                    'kecamatan' => [['nama', 'slug', 'jumlah']],
                    'jenis' => [['value', 'jumlah']],
                ],
            ])
            ->assertJsonPath('data.kategori.0.nama', 'Kuliner dan Perhotelan')
            ->assertJsonPath('data.kategori.0.jumlah', 1)
            ->assertJsonPath('data.jenis.1.value', 'karir')
            ->assertJsonPath('data.jenis.1.jumlah', 1);
    }

    public function test_lowongan_terbaru_dibatasi_jumlahnya(): void
    {
        Lowongan::factory()->tayang()->count(3)->create();

        $this->getJson('/api/lowongan/terbaru?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
