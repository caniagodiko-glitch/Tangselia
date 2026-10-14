<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Tempat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIDeskripsiTempatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.jeda_ms' => 0]);
    }

    public function test_perintah_menyusun_deskripsi_memakai_gemini(): void
    {
        config(['ai.key' => 'kunci-uji']);

        $kecamatan = Kecamatan::factory()->create(['nama' => 'Pamulang', 'slug' => 'pamulang']);
        $tempat = Tempat::factory()->create([
            'kecamatan_id' => $kecamatan->id,
            'deskripsi' => 'Deskripsi template OSM.',
            'deskripsi_sumber' => 'osm',
        ]);

        Http::fake(['*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => 'Kafe ini nyaman untuk bersantai di Pamulang.']]],
            ]],
        ])]);

        $this->artisan('deskripsi:ai', ['--fitur' => 'tempat', '--maks' => 5])->assertExitCode(0);

        $tempat->refresh();

        $this->assertSame('Kafe ini nyaman untuk bersantai di Pamulang.', $tempat->deskripsi);
        $this->assertSame('ai', $tempat->deskripsi_sumber);
        $this->assertContains('Gemini AI', array_column($tempat->atribusi, 'sumber'));
    }

    public function test_tanpa_kunci_api_deskripsi_tidak_diubah(): void
    {
        config(['ai.key' => null]);

        $tempat = Tempat::factory()->create([
            'deskripsi' => 'Deskripsi template OSM.',
            'deskripsi_sumber' => 'osm',
        ]);

        Http::fake();

        $this->artisan('deskripsi:ai', ['--fitur' => 'tempat', '--maks' => 5])->assertExitCode(0);

        $tempat->refresh();

        $this->assertSame('Deskripsi template OSM.', $tempat->deskripsi);
        $this->assertSame('osm', $tempat->deskripsi_sumber);
        Http::assertNothingSent();
    }
}
