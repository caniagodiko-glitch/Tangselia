<?php

namespace Tests\Feature;

use App\Models\Tempat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DirektoriGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config(['places.key' => 'kunci-uji']);
    }

    public function test_detail_live_mengembalikan_rating_dan_foto(): void
    {
        Tempat::factory()->create(['slug' => 'kafe-uji', 'nama' => 'Kafe Uji', 'status' => 'aktif', 'google_place_id' => null]);

        Http::fake([
            '*places:searchText*' => Http::response(['places' => [['id' => 'ChIJ_uji']]], 200),
            '*places/ChIJ_uji*' => Http::response([
                'id' => 'ChIJ_uji',
                'displayName' => ['text' => 'Kafe Uji'],
                'rating' => 4.7,
                'userRatingCount' => 123,
                'photos' => [['name' => 'places/ChIJ_uji/photos/abc']],
                'regularOpeningHours' => ['weekdayDescriptions' => ['Senin: 08.00-22.00']],
                'googleMapsUri' => 'https://maps.google.com/?cid=1',
            ], 200),
        ]);

        $this->getJson('/api/kuliner/kafe-uji/google')
            ->assertOk()
            ->assertJsonPath('data.rating', 4.7)
            ->assertJsonPath('data.jumlah_ulasan', 123)
            ->assertJsonPath('data.foto.0', 'places/ChIJ_uji/photos/abc')
            ->assertJsonPath('sumber', 'Google');
    }

    public function test_detail_tanpa_kunci_mengembalikan_null(): void
    {
        config(['places.key' => null]);

        Tempat::factory()->create(['slug' => 'kafe-uji', 'status' => 'aktif']);

        $this->getJson('/api/kuliner/kafe-uji/google')
            ->assertOk()
            ->assertJsonPath('data', null);

        Http::assertNothingSent();
    }

    public function test_proxy_foto_mengembalikan_berkas(): void
    {
        Tempat::factory()->create(['slug' => 'kafe-uji', 'status' => 'aktif', 'google_place_id' => 'ChIJ_uji']);

        Http::fake(['*' => Http::response('BINER', 200, ['Content-Type' => 'image/jpeg'])]);

        $this->get('/api/kuliner/kafe-uji/foto?photo='.urlencode('places/ChIJ_uji/photos/abc'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_proxy_foto_menolak_rujukan_luar_place(): void
    {
        Tempat::factory()->create(['slug' => 'kafe-uji', 'status' => 'aktif', 'google_place_id' => 'ChIJ_uji']);

        Http::fake(['*' => Http::response('BINER', 200)]);

        $this->get('/api/kuliner/kafe-uji/foto?photo='.urlencode('places/LAIN/photos/abc'))
            ->assertNotFound();
    }
}
