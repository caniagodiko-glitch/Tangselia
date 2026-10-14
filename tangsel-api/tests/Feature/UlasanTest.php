<?php

namespace Tests\Feature;

use App\Models\Tempat;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UlasanTest extends TestCase
{
    use RefreshDatabase;

    public function test_simpan_ulasan_tanpa_login_401(): void
    {
        $tempat = Tempat::factory()->create();

        $this->postJson('/api/ulasan', $this->payload($tempat->slug))->assertStatus(401);
    }

    public function test_warga_dapat_menyimpan_ulasan(): void
    {
        $tempat = Tempat::factory()->create();
        $warga = User::factory()->create();

        $this->actingAs($warga)
            ->postJson('/api/ulasan', $this->payload($tempat->slug))
            ->assertStatus(201)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.penulis', $warga->nama);

        $this->assertDatabaseHas('ulasan', [
            'direktori_type' => Tempat::class,
            'direktori_id' => $tempat->id,
            'user_id' => $warga->id,
            'rating' => 5,
            'status' => 'terbit',
        ]);
    }

    public function test_ulasan_dengan_foto_disimpan_lokal(): void
    {
        Storage::fake('public');

        $tempat = Tempat::factory()->create();
        $warga = User::factory()->create();

        $this->actingAs($warga)
            ->post('/api/ulasan', [
                'fitur' => 'kuliner',
                'slug' => $tempat->slug,
                'rating' => 4,
                'komentar' => 'Kopinya enak, tempatnya bersih.',
                'foto' => [UploadedFile::fake()->image('menu.jpg')],
            ])
            ->assertStatus(201);

        $ulasan = Ulasan::sole();

        $this->assertCount(1, $ulasan->foto);
        Storage::disk('public')->assertExists($ulasan->foto[0]);
    }

    public function test_validasi_rating_dan_komentar(): void
    {
        $tempat = Tempat::factory()->create();
        $warga = User::factory()->create();

        $this->actingAs($warga)
            ->postJson('/api/ulasan', [
                'fitur' => 'kuliner',
                'slug' => $tempat->slug,
                'rating' => 0,
                'komentar' => 'x',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating', 'komentar']);
    }

    public function test_ulasan_untuk_slug_tidak_ditemukan_404(): void
    {
        $warga = User::factory()->create();

        $this->actingAs($warga)
            ->postJson('/api/ulasan', $this->payload('tidak-ada'))
            ->assertStatus(404);
    }

    public function test_daftar_ulasan_publik(): void
    {
        $tempat = Tempat::factory()->create();
        $warga = User::factory()->create();

        Ulasan::factory()->count(2)->create([
            'direktori_type' => Tempat::class,
            'direktori_id' => $tempat->id,
            'user_id' => $warga->id,
        ]);

        $this->getJson('/api/kuliner/'.$tempat->slug.'/ulasan')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'rating', 'komentar', 'penulis']]]);
    }

    public function test_detail_menampilkan_ringkasan_dan_field_pengayaan(): void
    {
        $tempat = Tempat::factory()->create(['website' => 'https://contoh.id']);
        $warga = User::factory()->create();

        Ulasan::factory()->create([
            'direktori_type' => Tempat::class,
            'direktori_id' => $tempat->id,
            'user_id' => $warga->id,
            'rating' => 4,
        ]);

        Ulasan::factory()->create([
            'direktori_type' => Tempat::class,
            'direktori_id' => $tempat->id,
            'user_id' => $warga->id,
            'rating' => 5,
        ]);

        $this->getJson('/api/kuliner/'.$tempat->slug)
            ->assertOk()
            ->assertJsonPath('data.website', 'https://contoh.id')
            ->assertJsonPath('data.atribusi.0.sumber', 'OpenStreetMap')
            ->assertJsonPath('data.ulasan.rating', 4.5)
            ->assertJsonPath('data.ulasan.jumlah', 2)
            ->assertJsonStructure(['data' => ['deskripsi_sumber', 'atribusi', 'ulasan' => ['rating', 'jumlah', 'daftar']]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $slug): array
    {
        return [
            'fitur' => 'kuliner',
            'slug' => $slug,
            'rating' => 5,
            'komentar' => 'Tempatnya nyaman dan kopinya enak sekali.',
        ];
    }
}
