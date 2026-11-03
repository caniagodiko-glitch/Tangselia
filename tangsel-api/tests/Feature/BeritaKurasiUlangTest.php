<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BeritaKurasiUlangTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-03 12:00:00');

        Kategori::factory()->create([
            'scope' => 'berita',
            'nama' => 'Berita Kota',
            'slug' => 'berita-kota',
            'urutan' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_kurasi_ulang_menetapkan_status_dan_kecamatan(): void
    {
        $serpong = Kecamatan::factory()->create(['nama' => 'Serpong', 'slug' => 'serpong']);
        $ciputat = Kecamatan::factory()->create(['nama' => 'Ciputat', 'slug' => 'ciputat']);
        $ciputatTimur = Kecamatan::factory()->create(['nama' => 'Ciputat Timur', 'slug' => 'ciputat-timur']);

        $layak = Berita::factory()->agregat()->create([
            'judul' => 'Warga Ciputat Timur gotong royong',
            'ringkasan' => 'Kegiatan diikuti warga.',
            'sumber' => 'TangerangNews',
            'status' => 'published',
            'published_at' => Carbon::parse('2026-11-01 09:00:00'),
            'kecamatan_id' => $ciputat->id,
        ]);

        $luar = Berita::factory()->agregat()->create([
            'judul' => 'Pemkab Serang usulkan raperda',
            'ringkasan' => 'Rapat di Serang.',
            'sumber' => 'ANTARA News Banten',
            'status' => 'published',
            'published_at' => Carbon::parse('2026-11-01 09:00:00'),
            'kecamatan_id' => $serpong->id,
        ]);

        $tua = Berita::factory()->agregat()->create([
            'judul' => 'Warga Tangsel kerja bakti',
            'ringkasan' => 'Kegiatan warga.',
            'sumber' => 'TangerangNews',
            'status' => 'published',
            'published_at' => Carbon::parse('2024-05-07 09:00:00'),
            'kecamatan_id' => $serpong->id,
        ]);

        $ambigu = Berita::factory()->agregat()->create([
            'judul' => 'Rapat koordinasi provinsi digelar',
            'ringkasan' => 'Rapat membahas anggaran.',
            'sumber' => 'PosBanten',
            'status' => 'published',
            'published_at' => Carbon::parse('2026-11-01 09:00:00'),
        ]);

        $this->artisan('berita:kurasi-ulang')->assertSuccessful();

        $this->assertSame('published', $layak->fresh()->status);
        $this->assertSame($ciputatTimur->id, $layak->fresh()->kecamatan_id);
        $this->assertSame('archived', $luar->fresh()->status);
        $this->assertSame('archived', $tua->fresh()->status);
        $this->assertSame('draft', $ambigu->fresh()->status);
    }

    public function test_mode_uji_tidak_mengubah_data(): void
    {
        $berita = Berita::factory()->agregat()->create([
            'judul' => 'Pemkab Serang usulkan raperda',
            'sumber' => 'ANTARA News Banten',
            'status' => 'published',
            'published_at' => Carbon::parse('2026-11-01 09:00:00'),
        ]);

        $this->artisan('berita:kurasi-ulang', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('published', $berita->fresh()->status);
    }
}
