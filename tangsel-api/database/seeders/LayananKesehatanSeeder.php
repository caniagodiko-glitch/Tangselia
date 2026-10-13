<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\LayananKesehatan;
use Illuminate\Database\Seeder;

class LayananKesehatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Data contoh mengikuti tiga item mockup /kesehatan, ditambah apotek
     * (gap pada mockup) dan klinik agar enum jenis terwakili penuh.
     */
    public function run(): void
    {
        $layanan = [
            [
                'slug' => 'rsud-kota-tangerang-selatan',
                'kecamatan' => 'pamulang',
                'jenis' => 'rumah_sakit',
                'nama' => 'RSUD Kota Tangerang Selatan',
                'deskripsi' => 'Rumah sakit umum daerah tipe B dengan IGD 24 jam.',
                'alamat' => 'Jl. Puspemkot No. 1, Pamulang',
                'latitude' => -6.2790000,
                'longitude' => 106.7400000,
                'telepon' => '(021) 7412-000',
                'jam_buka' => null,
                'jam_tutup' => null,
                'layanan_24_jam' => true,
                'fasilitas' => ['IGD', 'Poliklinik', 'Laboratorium', 'Ambulans', 'Parkir'],
                'apotek' => null,
            ],
            [
                'slug' => 'puskesmas-pondok-aren',
                'kecamatan' => 'pondok-aren',
                'jenis' => 'puskesmas',
                'nama' => 'Puskesmas Pondok Aren',
                'deskripsi' => 'Puskesmas rawat inap dengan jadwal poli harian.',
                'alamat' => 'Jl. Raya Pondok Aren No. 15, Pondok Aren',
                'latitude' => -6.2760000,
                'longitude' => 106.7210000,
                'telepon' => '(021) 7413-001',
                'jam_buka' => null,
                'jam_tutup' => null,
                'layanan_24_jam' => true,
                'fasilitas' => ['Poliklinik', 'Laboratorium', 'Parkir'],
                'apotek' => null,
            ],
            [
                'slug' => 'rsud-pondok-aren',
                'kecamatan' => 'pondok-aren',
                'jenis' => 'rumah_sakit',
                'nama' => 'RSUD Pondok Aren',
                'deskripsi' => 'Rumah sakit umum tipe C dengan layanan IGD 24 jam.',
                'alamat' => 'Jl. JC. Tamanian No. 1, Pondok Aren',
                'latitude' => -6.2760000,
                'longitude' => 106.7210000,
                'telepon' => '(021) 7414-002',
                'jam_buka' => null,
                'jam_tutup' => null,
                'layanan_24_jam' => true,
                'fasilitas' => ['IGD', 'Poliklinik', 'Ambulans', 'Parkir'],
                'apotek' => null,
            ],
            [
                'slug' => 'apotek-sehat-bintaro',
                'kecamatan' => 'pondok-aren',
                'jenis' => 'apotek',
                'nama' => 'Apotek Sehat Bintaro',
                'deskripsi' => 'Apotek 24 jam dengan layanan resep dan konsultasi apoteker.',
                'alamat' => 'Jl. Bintaro Utama Sektor 3 No. 9, Pondok Aren',
                'latitude' => -6.2760000,
                'longitude' => 106.7210000,
                'telepon' => '(021) 7415-003',
                'jam_buka' => '00:00',
                'jam_tutup' => '00:00',
                'layanan_24_jam' => true,
                'fasilitas' => ['Parkir'],
                'apotek' => [
                    'jam_buka' => '24 jam',
                    'layanan_resep' => true,
                    'nomor_izin' => 'SIHA-2024-0112',
                ],
            ],
            [
                'slug' => 'apotek-warga-ciputat',
                'kecamatan' => 'ciputat',
                'jenis' => 'apotek',
                'nama' => 'Apotek Warga Ciputat',
                'deskripsi' => 'Apotek dengan layanan cek tekanan darah dan gula darah.',
                'alamat' => 'Jl. Dewi Sartika No. 44, Ciputat',
                'latitude' => -6.3180000,
                'longitude' => 106.6980000,
                'telepon' => '(021) 7416-004',
                'jam_buka' => '08:00',
                'jam_tutup' => '22:00',
                'layanan_24_jam' => false,
                'fasilitas' => ['Parkir'],
                'apotek' => [
                    'jam_buka' => '08:00-22:00',
                    'layanan_resep' => true,
                    'nomor_izin' => 'SIHA-2023-0458',
                ],
            ],
            [
                'slug' => 'klinik-pratama-serpong',
                'kecamatan' => 'serpong',
                'jenis' => 'klinik',
                'nama' => 'Klinik Pratama Serpong',
                'deskripsi' => 'Klinik pratama dengan dokter umum dan gigi.',
                'alamat' => 'Jl. Raya Serpong No. 78, Serpong',
                'latitude' => -6.3170000,
                'longitude' => 106.7210000,
                'telepon' => '(021) 7417-005',
                'jam_buka' => '08:00',
                'jam_tutup' => '20:00',
                'layanan_24_jam' => false,
                'fasilitas' => ['Poliklinik', 'Parkir'],
                'apotek' => null,
            ],
        ];

        foreach ($layanan as $item) {
            $kecamatanId = Kecamatan::where('slug', $item['kecamatan'])->first()->id;
            LayananKesehatan::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'kecamatan_id' => $kecamatanId,
                    'jenis' => $item['jenis'],
                    'nama' => $item['nama'],
                    'deskripsi' => $item['deskripsi'],
                    'alamat' => $item['alamat'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'telepon' => $item['telepon'],
                    'jam_buka' => $item['jam_buka'],
                    'jam_tutup' => $item['jam_tutup'],
                    'layanan_24_jam' => $item['layanan_24_jam'],
                    'fasilitas' => $item['fasilitas'],
                    'apotek' => $item['apotek'],
                    'status' => 'aktif',
                ]
            );
        }
    }
}
