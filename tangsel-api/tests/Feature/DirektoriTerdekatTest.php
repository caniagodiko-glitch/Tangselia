<?php

namespace Tests\Feature;

use App\Models\FasilitasOlahraga;
use App\Models\Tempat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirektoriTerdekatTest extends TestCase
{
    use RefreshDatabase;

    public function test_mengembalikan_tempat_lain_terurut_dari_terdekat(): void
    {
        $pusat = Tempat::factory()->create([
            'slug' => 'pusat',
            'latitude' => -6.3000,
            'longitude' => 106.7000,
        ]);

        $dekat = Tempat::factory()->create([
            'slug' => 'dekat',
            'latitude' => -6.3010,
            'longitude' => 106.7010,
        ]);

        $sedang = Tempat::factory()->create([
            'slug' => 'sedang',
            'latitude' => -6.3200,
            'longitude' => 106.7200,
        ]);

        $this->getJson('/api/kuliner/'.$pusat->slug.'/terdekat')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', $dekat->slug)
            ->assertJsonPath('data.1.slug', $sedang->slug)
            ->assertJsonStructure(['data' => [['id', 'nama', 'slug', 'jenis', 'alamat', 'kecamatan', 'jarak_m']]]);
    }

    public function test_mengecualikan_diri_sendiri_dan_tempat_jauh(): void
    {
        $pusat = Tempat::factory()->create([
            'slug' => 'pusat',
            'latitude' => -6.3000,
            'longitude' => 106.7000,
        ]);

        Tempat::factory()->create([
            'slug' => 'terlalu-jauh',
            'latitude' => -6.9000,
            'longitude' => 107.2000,
        ]);

        $this->getJson('/api/kuliner/'.$pusat->slug.'/terdekat')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_tidak_menghitung_entri_nonaktif(): void
    {
        $pusat = Tempat::factory()->create([
            'slug' => 'pusat',
            'latitude' => -6.3000,
            'longitude' => 106.7000,
        ]);

        Tempat::factory()->create([
            'slug' => 'nonaktif',
            'latitude' => -6.3010,
            'longitude' => 106.7010,
            'status' => 'nonaktif',
        ]);

        $this->getJson('/api/kuliner/'.$pusat->slug.'/terdekat')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_bekerja_untuk_fitur_olahraga(): void
    {
        $pusat = FasilitasOlahraga::factory()->create([
            'slug' => 'gor-pusat',
            'latitude' => -6.3000,
            'longitude' => 106.7000,
        ]);

        $tetangga = FasilitasOlahraga::factory()->create([
            'slug' => 'gor-tetangga',
            'latitude' => -6.3020,
            'longitude' => 106.7020,
        ]);

        $this->getJson('/api/olahraga/'.$pusat->slug.'/terdekat')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $tetangga->slug);
    }

    public function test_slug_tidak_ditemukan_404(): void
    {
        $this->getJson('/api/kuliner/tidak-ada/terdekat')->assertStatus(404);
    }
}
