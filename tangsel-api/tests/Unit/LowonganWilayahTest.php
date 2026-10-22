<?php

namespace Tests\Unit;

use App\Services\LowonganWilayah;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LowonganWilayahTest extends TestCase
{
    #[DataProvider('teksTangsel')]
    public function test_menyebut_tangsel(string $teks): void
    {
        $this->assertTrue(app(LowonganWilayah::class)->menyebutTangsel($teks));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function teksTangsel(): array
    {
        return [
            'nama panjang' => ['Lowongan di Kota Tangerang Selatan, Banten'],
            'nama singkat' => ['Warga Tangsel kini mudah mencari lowongan kerja'],
            'kecamatan' => ['Lowongan kerja di Serpong'],
            'bsd' => ['Job Fair di ICE BSD'],
            'kata lain' => ['Perusahaan membuka lowongan di Curug'],
        ];
    }

    #[DataProvider('teksWilayahLain')]
    public function test_menunjuk_wilayah_lain(string $teks): void
    {
        $this->assertTrue(app(LowonganWilayah::class)->menunjukWilayahLain($teks));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function teksWilayahLain(): array
    {
        return [
            'kota tangerang' => ['Enam lowongan kerja dibuka di Kota Tangerang'],
            'pemkot tangerang' => ['Jurus Pemkot Tangerang mudahkan pencari kerja'],
            'domain' => ['Liputan dari kotatangerang.com'],
        ];
    }

    public function test_kota_tangerang_selatan_bukan_wilayah_lain(): void
    {
        $wilayah = app(LowonganWilayah::class);

        $this->assertFalse($wilayah->menunjukWilayahLain('Lowongan di Kota Tangerang Selatan, Banten'));
        $this->assertTrue($wilayah->menyebutTangsel('Lowongan di Kota Tangerang Selatan, Banten'));
        $this->assertTrue($wilayah->layak('Lowongan di Kota Tangerang Selatan, Banten'));
    }

    public function test_berita_kota_tangerang_tidak_layak(): void
    {
        $this->assertFalse(app(LowonganWilayah::class)->layak('Lowongan Kerja di Kota Tangerang'));
    }

    public function test_berita_tanpa_penanda_wilayah_tidak_layak(): void
    {
        $this->assertFalse(app(LowonganWilayah::class)->layak('AEON rekrut besar-besaran di Jakarta'));
    }

    public function test_penanda_tangsel_mengalahkan_penanda_kota_lain(): void
    {
        $wilayah = app(LowonganWilayah::class);

        $teks = 'Kadin Tangsel membuka peluang magang untuk siswa';

        $this->assertTrue($wilayah->menyebutTangsel($teks));
        $this->assertTrue($wilayah->layak($teks));
    }

    public function test_job_board_tanpa_penanda_wilayah_tetap_layak(): void
    {
        $this->assertTrue(app(LowonganWilayah::class)->layak('PT Graha Sumber Prima Elektronik', false));
    }

    public function test_job_board_yang_menunjuk_kota_lain_tidak_layak(): void
    {
        $this->assertFalse(app(LowonganWilayah::class)->layak('Field Executive Area Medan', false));
    }
}
