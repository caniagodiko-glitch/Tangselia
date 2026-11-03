<?php

namespace Tests\Unit;

use App\Services\BeritaWilayah;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BeritaWilayahTest extends TestCase
{
    #[DataProvider('teksTangsel')]
    public function test_menyebut_tangsel(string $teks): void
    {
        $this->assertTrue(app(BeritaWilayah::class)->menyebutTangsel($teks));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function teksTangsel(): array
    {
        return [
            'nama panjang' => ['Pemerintah Kota Tangerang Selatan meresmikan taman'],
            'nama singkat' => ['Pemkot Tangsel uji coba tanam padi apung'],
            'kecamatan' => ['Tiang listrik roboh di Pondok Aren'],
            'kecamatan terpanjang' => ['Warga Ciputat Timur keluhkan lampu jalan'],
            'kawasan' => ['Job fair digelar di ICE BSD'],
            'kelurahan' => ['Tandon Ciater diresmikan'],
            'kecamatan utara' => ['Perumahan baru di Serpong Utara'],
        ];
    }

    #[DataProvider('teksBukanTangsel')]
    public function test_bukan_penanda_tangsel(string $teks): void
    {
        $this->assertFalse(app(BeritaWilayah::class)->menyebutTangsel($teks));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function teksBukanTangsel(): array
    {
        return [
            'tanpa wilayah' => ['Harga emas Antam naik Rp10.000 per gram'],
            'kata setuju' => ['Warga setuju pembangunan saluran air'],
            'ciputra' => ['Mal Ciputra menggelar promo akhir tahun'],
            'tangerang saja' => ['Kecamatan Karawaci di Kota Tangerang'],
        ];
    }

    #[DataProvider('teksWilayahLain')]
    public function test_menunjuk_wilayah_lain(string $teks): void
    {
        $this->assertTrue(app(BeritaWilayah::class)->menunjukWilayahLain($teks));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function teksWilayahLain(): array
    {
        return [
            'kota tangerang' => ['Pemkot Tangerang buka taman kota'],
            'kabupaten tangerang' => ['Pemkab Tangerang usulkan raperda'],
            'kecamatan lain' => ['Banjir merendam Karawaci'],
            'tangerang tanpa selatan' => ['Konser orkestra di Tangerang batal'],
            'kabupaten tangerang lain' => ['Pabrik plastik di Cikupa terbakar'],
            'kota lain' => ['Pemkab Serang menggelar rapat'],
            'nasional' => ['Pemerintah Jakarta menata trotoar'],
        ];
    }

    public function test_kota_tangerang_selatan_bukan_wilayah_lain(): void
    {
        $wilayah = app(BeritaWilayah::class);

        $this->assertFalse($wilayah->menunjukWilayahLain('Pemerintah Kota Tangerang Selatan meresmikan taman'));
        $this->assertFalse($wilayah->menunjukWilayahLain('Pemkot Tangsel uji coba tanam padi'));
        $this->assertTrue($wilayah->menyebutTangsel('Pemerintah Kota Tangerang Selatan meresmikan taman'));
    }

    public function test_sumber_lokal_tetap_diterima_tanpa_penanda_wilayah(): void
    {
        $keputusan = app(BeritaWilayah::class)->keputusan(
            'PLN evakuasi gardu portal yang roboh',
            'Petugas mengevakuasi gardu listrik.',
            'Bing News - Tangerang Selatan',
        );

        $this->assertSame(BeritaWilayah::TERIMA, $keputusan);
    }

    public function test_sumber_lokal_yang_menunjuk_wilayah_lain_draft(): void
    {
        $keputusan = app(BeritaWilayah::class)->keputusan(
            'Warga Tangsel keluhkan banjir, ini kata Pemkot Tangerang',
            null,
            'Bing News - Tangerang Selatan',
        );

        $this->assertSame(BeritaWilayah::DRAFT, $keputusan);
    }

    public function test_sumber_umum_dengan_penanda_tangsel_diterima(): void
    {
        $keputusan = app(BeritaWilayah::class)->keputusan(
            'Tiang listrik roboh di Pondok Aren',
            'Peristiwa terjadi pagi ini.',
            'ANTARA News Banten',
        );

        $this->assertSame(BeritaWilayah::TERIMA, $keputusan);
    }

    public function test_sumber_umum_tanpa_penanda_tangsel_menjadi_draft(): void
    {
        $keputusan = app(BeritaWilayah::class)->keputusan(
            'Rapat koordinasi tingkat provinsi digelar',
            'Rapat membahas anggaran.',
            'PosBanten',
        );

        $this->assertSame(BeritaWilayah::DRAFT, $keputusan);
    }

    public function test_sumber_umum_yang_menunjuk_wilayah_lain_ditolak(): void
    {
        $keputusan = app(BeritaWilayah::class)->keputusan(
            'Pemkab Serang usulkan 12 raperda',
            'Pemerintah kabupaten mengusulkan raperda.',
            'ANTARA News Banten',
        );

        $this->assertSame(BeritaWilayah::TOLAK, $keputusan);
    }

    public function test_sumber_nasional_tanpa_penanda_tangsel_ditolak(): void
    {
        $keputusan = app(BeritaWilayah::class)->keputusan(
            'Cadangan gas Eropa terendah sejak 2013',
            'Badan energi internasional merilis laporan.',
            'ANTARA News Nasional',
        );

        $this->assertSame(BeritaWilayah::TOLAK, $keputusan);
    }

    public function test_sumber_nasional_dengan_penanda_tangsel_diterima(): void
    {
        $keputusan = app(BeritaWilayah::class)->keputusan(
            'Pemkot Tangsel resmikan taman kota',
            'Taman dibuka untuk warga.',
            'ANTARA News Nasional',
        );

        $this->assertSame(BeritaWilayah::TERIMA, $keputusan);
    }

    public function test_akhiran_nama_penerbit_tidak_dihitung_wilayah_lain(): void
    {
        $keputusan = app(BeritaWilayah::class)->keputusan(
            'KNPI Tangsel Gelar Forum Kebijakan Pemuda - Tangerang Online',
            'Forum membahas program prioritas.',
            'Tangerang Online',
        );

        $this->assertSame(BeritaWilayah::TERIMA, $keputusan);
    }

    public function test_berita_lama_ditolak(): void
    {
        $wilayah = app(BeritaWilayah::class);

        $this->assertTrue($wilayah->terlaluTua(
            Carbon::parse('2024-05-07 09:00:00'),
            Carbon::parse('2026-11-03 12:00:00'),
        ));

        $this->assertFalse($wilayah->terlaluTua(
            Carbon::parse('2026-10-20 09:00:00'),
            Carbon::parse('2026-11-03 12:00:00'),
        ));

        $this->assertFalse($wilayah->terlaluTua(null, Carbon::parse('2026-11-03 12:00:00')));
    }
}
