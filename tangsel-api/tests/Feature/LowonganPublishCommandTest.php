<?php

namespace Tests\Feature;

use App\Models\Lowongan;
use App\Models\Sumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowonganPublishCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_menayangkan_lowongan_baru_yang_relevan_dan_punya_url_langsung(): void
    {
        $sumber = $this->sumberKitalulus();
        $lowongan = Lowongan::factory()->create([
            'sumber_id' => $sumber->id,
            'status' => 'baru',
            'relevan' => true,
            'url_asli' => 'https://www.kitalulus.com/lowongan/detail/backend-developer',
            'published_at' => null,
        ]);

        $this->artisan('lowongan:publish', ['--source' => 'Kitalulus'])->assertSuccessful();

        $lowongan->refresh();

        $this->assertSame('tayang', $lowongan->status);
        $this->assertNotNull($lowongan->tayang_at);
        $this->assertNotNull($lowongan->published_at);
        $this->assertNotNull($lowongan->kedaluwarsa_at);
        $this->assertTrue($lowongan->kedaluwarsa_at->isFuture());
    }

    public function test_melewati_tautan_redirect_dan_lowongan_tidak_relevan(): void
    {
        $sumber = $this->sumberKitalulus();

        Lowongan::factory()->create([
            'sumber_id' => $sumber->id,
            'url_asli' => 'https://news.google.com/rss/articles/abc',
        ]);

        Lowongan::factory()->create([
            'sumber_id' => $sumber->id,
            'url_asli' => 'https://www.kitalulus.com/lowongan/detail/tidak-relevan',
            'relevan' => false,
        ]);

        $this->artisan('lowongan:publish')->assertSuccessful();

        $this->assertSame(2, Lowongan::where('status', 'baru')->count());
        $this->assertSame(0, Lowongan::where('status', 'tayang')->count());
    }

    public function test_batas_jumlah_dihormati(): void
    {
        $sumber = $this->sumberKitalulus();

        foreach (range(1, 3) as $nomor) {
            Lowongan::factory()->create([
                'sumber_id' => $sumber->id,
                'url_asli' => 'https://www.kitalulus.com/lowongan/detail/contoh-'.$nomor,
            ]);
        }

        $this->artisan('lowongan:publish', ['--limit' => 2])->assertSuccessful();

        $this->assertSame(2, Lowongan::where('status', 'tayang')->count());
        $this->assertSame(1, Lowongan::where('status', 'baru')->count());
    }

    private function sumberKitalulus(): Sumber
    {
        return Sumber::factory()->lowongan()->create([
            'nama' => 'Kitalulus - Lowongan Tangerang Selatan',
            'jenis' => 'scraping',
            'feed_url' => 'https://www.kitalulus.com/lowongan/in-kota-tangerang-selatan',
        ]);
    }
}
