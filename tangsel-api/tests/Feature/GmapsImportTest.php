<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Tempat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GmapsImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Path berkas JSON sementara yang dibuat tiap test.
     */
    private ?string $berkas = null;

    protected function setUp(): void
    {
        parent::setUp();

        Kecamatan::factory()->create(['nama' => 'Pamulang', 'slug' => 'pamulang']);
    }

    protected function tearDown(): void
    {
        if ($this->berkas !== null && file_exists($this->berkas)) {
            unlink($this->berkas);
        }

        parent::tearDown();
    }

    public function test_mode_perkaya_menggabungkan_tanpa_duplikat(): void
    {
        $lama = Tempat::factory()->create([
            'nama' => 'Kopi Uji',
            'slug' => 'kopi-uji',
            'alamat' => 'Jl. Uji, Pamulang',
            'latitude' => -6.3439,
            'longitude' => 106.7398,
            'rating' => 0,
            'rating_kount' => 0,
            'telepon' => null,
            'website' => null,
            'gambar' => null,
        ]);

        $this->tulisJson([
            $this->entri([
                'nama' => 'Kopi Uji',
                'alamat' => 'Jl. Uji No. 1, Pamulang, Tangerang Selatan',
                'latitude' => -6.3439,
                'longitude' => 106.7398,
                'rating' => 4.7,
                'rating_kount' => 128,
                'telepon' => '0812-3456-7890',
                'website' => 'https://kopiuji.example',
                'foto_url' => ['https://example.test/foto/kopi-uji.jpg'],
            ]),
            $this->entri([
                'nama' => 'Warung Baru',
                'alamat' => 'Jl. Baru No. 2, Pamulang, Tangerang Selatan',
                'latitude' => -6.3450,
                'longitude' => 106.7401,
                'rating' => 4.2,
            ]),
        ]);

        $this->artisan('gmaps:import', [
            'file' => $this->berkas,
            '--fitur' => 'tempat',
            '--mode' => 'perkaya',
        ])->assertSuccessful();

        // Tidak menggandakan baris lama.
        $this->assertSame(2, Tempat::count());

        $lama->refresh();
        $this->assertEquals(4.7, $lama->rating);
        $this->assertSame(128, $lama->rating_kount);
        $this->assertSame('0812-3456-7890', $lama->telepon);
        $this->assertSame('https://kopiuji.example', $lama->website);
        $this->assertSame('https://example.test/foto/kopi-uji.jpg', $lama->gambar);
        $this->assertContains('Google Maps', array_column($lama->atribusi, 'sumber'));

        // Baris baru dibuat dengan penanda sumber gmaps.
        $baru = Tempat::where('nama', 'Warung Baru')->sole();
        $this->assertSame('gmaps', $baru->deskripsi_sumber);
        $this->assertSame('Pamulang', $baru->kecamatan->nama);
    }

    public function test_mode_perkaya_tidak_menimpa_data_yang_sudah_terisi(): void
    {
        $lama = Tempat::factory()->create([
            'nama' => 'Kopi Uji',
            'slug' => 'kopi-uji',
            'alamat' => 'Jl. Uji, Pamulang',
            'latitude' => -6.3439,
            'longitude' => 106.7398,
            'rating' => 5.0,
            'telepon' => '021-999',
            'website' => 'https://lama.example',
            'gambar' => 'direktori/tempat/lama.jpg',
        ]);

        $this->tulisJson([
            $this->entri([
                'nama' => 'Kopi Uji',
                'alamat' => 'Jl. Uji No. 1, Pamulang',
                'latitude' => -6.3439,
                'longitude' => 106.7398,
                'rating' => 3.1,
                'telepon' => '0812-0000',
                'website' => 'https://baru.example',
                'foto_url' => ['https://example.test/foto/baru.jpg'],
            ]),
        ]);

        $this->artisan('gmaps:import', [
            'file' => $this->berkas,
            '--fitur' => 'tempat',
            '--mode' => 'perkaya',
        ])->assertSuccessful();

        $lama->refresh();
        $this->assertEquals(5.0, $lama->rating);
        $this->assertSame('021-999', $lama->telepon);
        $this->assertSame('https://lama.example', $lama->website);
        $this->assertSame('direktori/tempat/lama.jpg', $lama->gambar);
    }

    public function test_mode_tambah_selalu_membuat_baris_baru(): void
    {
        Tempat::factory()->create([
            'nama' => 'Kopi Uji',
            'slug' => 'kopi-uji',
            'alamat' => 'Jl. Uji, Pamulang',
            'latitude' => -6.3439,
            'longitude' => 106.7398,
        ]);

        $this->tulisJson([
            $this->entri([
                'nama' => 'Kopi Uji',
                'alamat' => 'Jl. Uji No. 1, Pamulang',
                'latitude' => -6.3439,
                'longitude' => 106.7398,
            ]),
        ]);

        $this->artisan('gmaps:import', [
            'file' => $this->berkas,
            '--fitur' => 'tempat',
            '--mode' => 'tambah',
        ])->assertSuccessful();

        $this->assertSame(2, Tempat::count());
    }

    /**
     * @param  list<array<string, mixed>>  $entri
     */
    private function tulisJson(array $entri): void
    {
        $this->berkas = tempnam(sys_get_temp_dir(), 'gmaps-');
        file_put_contents($this->berkas, json_encode($entri));
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function entri(array $ubah): array
    {
        return array_merge([
            'nama' => 'Tempat Uji',
            'alamat' => 'Jl. Uji, Pamulang, Tangerang Selatan',
            'latitude' => -6.3439,
            'longitude' => 106.7398,
            'kategori' => 'Restoran',
            'telepon' => null,
            'website' => null,
            'rating' => null,
            'rating_kount' => null,
            'jam_buka_teks' => null,
            'jam_per_hari' => null,
            'rentang_harga' => null,
            'fasilitas' => [],
            'foto_url' => [],
            'google_maps_url' => null,
        ], $ubah);
    }
}
