<?php

namespace Tests\Unit;

use App\Services\AIEkstraksiLowongan;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AIEkstraksiLowonganTest extends TestCase
{
    public function test_ekstraksi_mengembalikan_null_tanpa_kunci_api(): void
    {
        config(['ai.key' => null]);

        Http::fake();

        $this->assertNull(app(AIEkstraksiLowongan::class)->ekstrak('Judul', 'Isi'));
    }

    public function test_ekstraksi_membaca_json_dari_gemini(): void
    {
        config(['ai.key' => 'kunci-uji']);

        $respons = $this->responsGemini([
            'posisi' => 'Front Office',
            'perusahaan' => 'PT Grand Indonesia',
            'deskripsi' => 'Melayani tamu hotel.',
            'kualifikasi' => 'Minimal SMA.',
            'cara_apply' => 'Kirim lamaran ke surel hotel.',
        ]);

        Http::fake(['*' => Http::response($respons)]);

        $hasil = app(AIEkstraksiLowongan::class)->ekstrak('Judul', 'Isi');

        $this->assertSame('Front Office', $hasil['posisi']);
        $this->assertSame('PT Grand Indonesia', $hasil['perusahaan']);
        $this->assertSame('Melayani tamu hotel.', $hasil['deskripsi']);
        $this->assertSame('Minimal SMA.', $hasil['kualifikasi']);
        $this->assertSame('Kirim lamaran ke surel hotel.', $hasil['cara_apply']);
    }

    public function test_ekstraksi_mengembalikan_null_bila_respons_bukan_json(): void
    {
        config(['ai.key' => 'kunci-uji']);

        Http::fake(['*' => Http::response($this->responsGeminiRaw('bukan json'))]);

        $this->assertNull(app(AIEkstraksiLowongan::class)->ekstrak('Judul', 'Isi'));
    }

    /**
     * @param  string  $judul  judul mentah dari feed
     * @param  ?string  $perusahaan  nama perusahaan yang diharapkan
     * @param  ?string  $posisi  nama posisi yang diharapkan
     */
    #[DataProvider('polaJudul')]
    public function test_parse_dari_judul_mengenali_pola_job_board(string $judul, ?string $perusahaan, ?string $posisi): void
    {
        $hasil = app(AIEkstraksiLowongan::class)->parseDariJudul($judul);

        $this->assertSame($perusahaan, $hasil['perusahaan']);
        $this->assertSame($posisi, $hasil['posisi']);
    }

    /**
     * @return array<string, array{0: string, 1: ?string, 2: ?string}>
     */
    public static function polaJudul(): array
    {
        return [
            'hiring' => [
                'Dexa Group hiring Content Creator Officer in Kota Tangerang Selatan, Banten, Indonesia - LinkedIn',
                'Dexa Group',
                'Content Creator Officer',
            ],
            'membuka lowongan' => [
                'FIT HUB membuka lowongan Personal Trainer - Tangerang Selatan di Tangerang Selatan - LinkedIn',
                'FIT HUB',
                'Personal Trainer',
            ],
            'posisi di perusahaan' => [
                'Technical and Integration Coordinator di PT. Softex Indonesia — Tangerang Selatan - LinkedIn',
                'PT. Softex Indonesia',
                'Technical and Integration Coordinator',
            ],
            'perusahaan posisi' => [
                'BP Consulting Group - Management Consultant provided pay range - LinkedIn',
                'BP Consulting Group',
                'Management Consultant',
            ],
            'posisi by perusahaan' => [
                'Sales Trainee Academy by Ruangguru Tangerang Selatan Bintaro Plus - LinkedIn',
                'Ruangguru Tangerang Selatan Bintaro Plus',
                'Sales Trainee Academy',
            ],
            'loker' => [
                'Loker Reni Pondok Petir di Banten - Glints',
                'Reni Pondok Petir',
                null,
            ],
            'lowongan kerja' => [
                'Lowongan Kerja PT Graha Sumber Prima Elektronik - Karir.com',
                'PT Graha Sumber Prima Elektronik',
                null,
            ],
            'posisi di perusahaan dengan ekor klausa' => [
                'Lowongan Kerja Oktober 2026 Posisi Admin di Konsultan Sertifikasiku, Gaji Rp 4,5 Juta',
                'Konsultan Sertifikasiku',
                'Admin',
            ],
            'lowongan kerja dengan keterangan bulan' => [
                'Lowongan Kerja Oktober 2026 Posisi Barista',
                null,
                'Barista',
            ],
            'posisi dan perusahaan dengan tanda baca' => [
                'Kerja untuk Berbagai Lulusan @ Pharos Indonesia',
                'Pharos Indonesia',
                'Kerja untuk Berbagai Lulusan',
            ],
        ];
    }

    public function test_rapikan_judul_membuang_sufiks_sumber(): void
    {
        $layanan = app(AIEkstraksiLowongan::class);

        $this->assertSame(
            'Staff Admin di PT Contoh',
            $layanan->rapikanJudul('Staff Admin di PT Contoh - LinkedIn', 'LinkedIn')
        );

        $this->assertSame(
            'Staff Admin di PT Contoh',
            $layanan->rapikanJudul('Staff Admin di PT Contoh | Glints TapLoker', 'Glints TapLoker')
        );
    }

    /**
     * Membentuk struktur respons Gemini untuk kebutuhan pengujian.
     *
     * @param  array<string, string>  $isi
     * @return array<string, mixed>
     */
    private function responsGemini(array $isi): array
    {
        return $this->responsGeminiRaw((string) json_encode($isi));
    }

    /**
     * @return array<string, mixed>
     */
    private function responsGeminiRaw(string $teks): array
    {
        return [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => $teks],
                        ],
                    ],
                ],
            ],
        ];
    }
}
