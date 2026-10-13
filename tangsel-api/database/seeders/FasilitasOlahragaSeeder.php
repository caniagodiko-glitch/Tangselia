<?php

namespace Database\Seeders;

use App\Models\FasilitasOlahraga;
use App\Models\Kecamatan;
use Illuminate\Database\Seeder;

class FasilitasOlahragaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Data contoh mengikuti dua item mockup /olahraga, ditambah empat lagi
     * agar seluruh enum jenis terwakili.
     */
    public function run(): void
    {
        $fasilitas = [
            [
                'slug' => 'gor-graha-bulutangkis',
                'kecamatan' => 'pamulang',
                'jenis' => 'gor_bulutangkis',
                'nama' => 'GOR Graha Bulutangkis',
                'deskripsi' => 'GOR bulutangkis dengan enam lapangan indoor dan tribun penonton.',
                'alamat' => 'Jl. Graha Raya No. 5, Pamulang',
                'latitude' => -6.2790000,
                'longitude' => 106.7400000,
                'kapasitas' => 60,
                'tarif_sewa' => 'Rp35.000/jam',
                'fasilitas' => ['Bola', 'Raket', 'Tempat Mandi', 'Parkir'],
                'rating' => 4.7,
            ],
            [
                'slug' => 'lapangan-futsal-bsd-serpong',
                'kecamatan' => 'serpong',
                'jenis' => 'lapangan_futsal',
                'nama' => 'Lapangan Futsal BSD Serpong',
                'deskripsi' => 'Lapangan futsal rumput sintetis dengan lampu malam.',
                'alamat' => 'Jl. Pahlawan Serpong No. 10, Serpong',
                'latitude' => -6.3170000,
                'longitude' => 106.7210000,
                'kapasitas' => 22,
                'tarif_sewa' => 'Rp50.000/jam',
                'fasilitas' => ['Lapangan', 'Tempat Mandi', 'Kantin'],
                'rating' => 4.5,
            ],
            [
                'slug' => 'kolam-renang-bsd-city',
                'kecamatan' => 'serpong',
                'jenis' => 'kolam_renang',
                'nama' => 'Kolam Renang BSD City',
                'deskripsi' => 'Kolam renang olimpiade dan kolam anak untuk keluarga.',
                'alamat' => 'Jl. Raya BSD City, Serpong',
                'latitude' => -6.3050000,
                'longitude' => 106.7160000,
                'kapasitas' => 80,
                'tarif_sewa' => 'Rp20.000/sesi',
                'fasilitas' => ['Tempat Mandi', 'Kantin', 'Parkir'],
                'rating' => 4.6,
            ],
            [
                'slug' => 'lapangan-basket-ciputat',
                'kecamatan' => 'ciputat',
                'jenis' => 'lapangan_basket',
                'nama' => 'Lapangan Basket Ciputat',
                'deskripsi' => 'Lapangan basket outdoor dengan pencahayaan malam.',
                'alamat' => 'Jl. Ciputat Raya No. 30, Ciputat',
                'latitude' => -6.3180000,
                'longitude' => 106.6980000,
                'kapasitas' => 30,
                'tarif_sewa' => 'Rp20.000/jam',
                'fasilitas' => ['Lapangan', 'Parkir'],
                'rating' => 4.3,
            ],
            [
                'slug' => 'gor-bulutangkis-serpong-utara',
                'kecamatan' => 'serpong-utara',
                'jenis' => 'gor_bulutangkis',
                'nama' => 'GOR Bulutangkis Serpong Utara',
                'deskripsi' => 'GOR komunitas dengan empat lapangan dan pelatih tersedia.',
                'alamat' => 'Jl. Ciater Barat No. 8, Serpong Utara',
                'latitude' => -6.3000000,
                'longitude' => 106.7100000,
                'kapasitas' => 40,
                'tarif_sewa' => 'Rp30.000/jam',
                'fasilitas' => ['Bola', 'Raket', 'Tempat Mandi'],
                'rating' => 4.4,
            ],
            [
                'slug' => 'gym-fitcenter-bintaro',
                'kecamatan' => 'pondok-aren',
                'jenis' => 'gym',
                'nama' => 'Gym FitCenter Bintaro',
                'deskripsi' => 'Pusat kebugaran dengan alat lengkap dan kelas grup.',
                'alamat' => 'Jl. Bintaro Sektor 3 No. 2, Pondok Aren',
                'latitude' => -6.2760000,
                'longitude' => 106.7210000,
                'kapasitas' => 50,
                'tarif_sewa' => 'Rp50.000/sesi',
                'fasilitas' => ['Tempat Mandi', 'Parkir', 'Kantin'],
                'rating' => 4.8,
            ],
        ];

        foreach ($fasilitas as $item) {
            $kecamatanId = Kecamatan::where('slug', $item['kecamatan'])->first()->id;
            FasilitasOlahraga::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'kecamatan_id' => $kecamatanId,
                    'jenis' => $item['jenis'],
                    'nama' => $item['nama'],
                    'deskripsi' => $item['deskripsi'],
                    'alamat' => $item['alamat'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'kapasitas' => $item['kapasitas'],
                    'tarif_sewa' => $item['tarif_sewa'],
                    'fasilitas' => $item['fasilitas'],
                    'rating' => $item['rating'],
                    'gambar' => null,
                    'status' => 'aktif',
                ]
            );
        }
    }
}
