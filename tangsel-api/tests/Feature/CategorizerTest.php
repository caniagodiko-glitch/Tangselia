<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Services\Categorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategorizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Kategori::factory()->create([
            'scope' => 'berita',
            'nama' => 'Berita Kota',
            'slug' => 'berita-kota',
            'urutan' => 1,
        ]);

        foreach (['Ciputat', 'Ciputat Timur', 'Pamulang', 'Pondok Aren', 'Serpong', 'Serpong Utara', 'Setu'] as $nama) {
            Kecamatan::factory()->create([
                'nama' => $nama,
                'slug' => Str::slug($nama),
            ]);
        }
    }

    public function test_kecamatan_paling_spesifik_dipilih(): void
    {
        $this->assertSame(
            $this->idKecamatan('Ciputat Timur'),
            app(Categorizer::class)->categorize('Warga Ciputat Timur keluhkan lampu jalan')['kecamatan_id'],
        );

        $this->assertSame(
            $this->idKecamatan('Serpong Utara'),
            app(Categorizer::class)->categorize('Perumahan baru di Serpong Utara')['kecamatan_id'],
        );
    }

    public function test_kecamatan_induk_tetap_dikenali(): void
    {
        $this->assertSame(
            $this->idKecamatan('Ciputat'),
            app(Categorizer::class)->categorize('Pasar Ciputat ramai')['kecamatan_id'],
        );

        $this->assertSame(
            $this->idKecamatan('Serpong'),
            app(Categorizer::class)->categorize('Stasiun Serpong diperbaiki')['kecamatan_id'],
        );
    }

    public function test_kata_setuju_bukan_setu(): void
    {
        $hasil = app(Categorizer::class)->categorize(
            'Warga setuju pembangunan saluran',
            'Persetujuan warga diberikan pekan ini.',
        );

        $this->assertNull($hasil['kecamatan_id']);
    }

    public function test_judul_diprioritaskan_daripada_ringkasan(): void
    {
        $hasil = app(Categorizer::class)->categorize(
            'Warga Pamulang mengeluh sampah',
            'Kejadian serupa terjadi di Ciputat.',
        );

        $this->assertSame($this->idKecamatan('Pamulang'), $hasil['kecamatan_id']);
    }

    public function test_kelurahan_dipakai_saat_kecamatan_tidak_disebut(): void
    {
        $this->assertSame(
            $this->idKecamatan('Serpong'),
            app(Categorizer::class)->categorize('Tandon Ciater diresmikan')['kecamatan_id'],
        );

        $this->assertSame(
            $this->idKecamatan('Ciputat Timur'),
            app(Categorizer::class)->categorize('Banjir di Rempoa surut')['kecamatan_id'],
        );
    }

    private function idKecamatan(string $nama): int
    {
        return (int) Kecamatan::where('nama', $nama)->value('id');
    }
}
