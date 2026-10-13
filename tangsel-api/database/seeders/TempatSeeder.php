<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Tempat;
use Illuminate\Database\Seeder;

class TempatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Data contoh mengikuti empat item pada mockup /kuliner ditambah warkop
     * agar ketiga enum jenis terwakili.
     */
    public function run(): void
    {
        $tempat = [
            [
                'slug' => 'kala-di-kala-space-coffee',
                'kecamatan' => 'pamulang',
                'jenis' => 'kafe',
                'nama' => 'Kala di Kala Space & Coffee',
                'deskripsi' => 'Kafe cozy dengan area kerja dan kopi susu andalan di kawasan Pamulang.',
                'alamat' => 'Jl. Siliwangi No. 12, Pamulang',
                'latitude' => -6.2790000,
                'longitude' => 106.7400000,
                'jam_buka' => '09:00',
                'jam_tutup' => '22:00',
                'rentang_harga' => 'Rp25.000 - Rp50.000',
                'rating' => 4.8,
                'rating_kount' => 132,
                'fasilitas' => ['WiFi Cepat', 'Stop Kontak', 'Area Smoking', 'Mushola', 'Parkir Luas'],
                'menu' => [
                    ['nama' => 'Kopi Susu Kala', 'harga' => 'Rp20.000'],
                    ['nama' => 'Matcha Latte', 'harga' => 'Rp25.000'],
                    ['nama' => 'Croissant Butter', 'harga' => 'Rp22.000'],
                ],
            ],
            [
                'slug' => 'ruang-seduh-bambu-kuning',
                'kecamatan' => 'ciputat',
                'jenis' => 'kafe',
                'nama' => 'Ruang Seduh Bambu Kuning',
                'deskripsi' => 'Kedai kopi rumahan di Ciputat dengan biji kopi lokal pilihan.',
                'alamat' => 'Jl. Bambu Kuning Raya No. 3, Ciputat',
                'latitude' => -6.3180000,
                'longitude' => 106.6980000,
                'jam_buka' => '08:00',
                'jam_tutup' => '21:00',
                'rentang_harga' => '< Rp25.000',
                'rating' => 4.7,
                'rating_kount' => 98,
                'fasilitas' => ['WiFi Cepat', 'Stop Kontak', 'Mushola'],
                'menu' => [
                    ['nama' => 'V60 Single Origin', 'harga' => 'Rp28.000'],
                    ['nama' => 'Es Kopi Gula Aren', 'harga' => 'Rp18.000'],
                ],
            ],
            [
                'slug' => 'sore-di-serpong',
                'kecamatan' => 'serpong',
                'jenis' => 'restoran',
                'nama' => 'Sore di Serpong',
                'deskripsi' => 'Restoran keluarga dengan menu nusantara dan area terbuka.',
                'alamat' => 'Jl. Raya Serpong No. 45, Serpong',
                'latitude' => -6.3170000,
                'longitude' => 106.7210000,
                'jam_buka' => '10:00',
                'jam_tutup' => '22:00',
                'rentang_harga' => '> Rp50.000',
                'rating' => 4.9,
                'rating_kount' => 214,
                'fasilitas' => ['Parkir Luas', 'Mushola', 'Area Smoking'],
                'menu' => [
                    ['nama' => 'Nasi Liwet Komplit', 'harga' => 'Rp65.000'],
                    ['nama' => 'Ayam Bakar Taliwang', 'harga' => 'Rp55.000'],
                ],
            ],
            [
                'slug' => 'kopitagram',
                'kecamatan' => 'pondok-aren',
                'jenis' => 'kafe',
                'nama' => 'Kopitagram',
                'deskripsi' => 'Kopitagram modern di kawasan Bintaro, cocok untuk nongkrong.',
                'alamat' => 'Jl. Bintaro Utama Sektor 7, Pondok Aren',
                'latitude' => -6.2760000,
                'longitude' => 106.7210000,
                'jam_buka' => '08:00',
                'jam_tutup' => '23:00',
                'rentang_harga' => 'Rp25.000 - Rp50.000',
                'rating' => 4.6,
                'rating_kount' => 176,
                'fasilitas' => ['WiFi Cepat', 'Stop Kontak', 'Parkir Luas'],
                'menu' => [
                    ['nama' => 'Kopitagram Special', 'harga' => 'Rp24.000'],
                    ['nama' => 'Kaya Toast', 'harga' => 'Rp19.000'],
                ],
            ],
            [
                'slug' => 'warkop-88-ciputat',
                'kecamatan' => 'ciputat',
                'jenis' => 'warkop',
                'nama' => 'Warkop 88 Ciputat',
                'deskripsi' => 'Warung kopi pinggir jalan buka larut, favorit anak muda Ciputat.',
                'alamat' => 'Jl. KH. Ahmad Dahlan No. 88, Ciputat',
                'latitude' => -6.3050000,
                'longitude' => 106.7160000,
                'jam_buka' => '07:00',
                'jam_tutup' => '23:00',
                'rentang_harga' => '< Rp25.000',
                'rating' => 4.4,
                'rating_kount' => 87,
                'fasilitas' => ['WiFi Cepat', 'Parkir Luas'],
                'menu' => [
                    ['nama' => 'Es Teh Manis', 'harga' => 'Rp5.000'],
                    ['nama' => 'Mie Goreng', 'harga' => 'Rp15.000'],
                ],
            ],
            [
                'slug' => 'sate-khas-serpong',
                'kecamatan' => 'serpong',
                'jenis' => 'restoran',
                'nama' => 'Sate Khas Serpong',
                'deskripsi' => 'Sate ayam dan kambing dengan bumbu kacang legendaris.',
                'alamat' => 'Jl. Ciater Raya No. 21, Serpong',
                'latitude' => -6.3050000,
                'longitude' => 106.7160000,
                'jam_buka' => '10:00',
                'jam_tutup' => '21:00',
                'rentang_harga' => 'Rp25.000 - Rp50.000',
                'rating' => 4.7,
                'rating_kount' => 155,
                'fasilitas' => ['Parkir Luas', 'Mushola'],
                'menu' => [
                    ['nama' => 'Sate Ayam (10 tusuk)', 'harga' => 'Rp35.000'],
                    ['nama' => 'Sate Kambing (10 tusuk)', 'harga' => 'Rp50.000'],
                ],
            ],
        ];

        foreach ($tempat as $item) {
            $kecamatanId = Kecamatan::where('slug', $item['kecamatan'])->first()->id;
            Tempat::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'kecamatan_id' => $kecamatanId,
                    'jenis' => $item['jenis'],
                    'nama' => $item['nama'],
                    'deskripsi' => $item['deskripsi'],
                    'alamat' => $item['alamat'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'jam_buka' => $item['jam_buka'],
                    'jam_tutup' => $item['jam_tutup'],
                    'rentang_harga' => $item['rentang_harga'],
                    'rating' => $item['rating'],
                    'rating_kount' => $item['rating_kount'],
                    'fasilitas' => $item['fasilitas'],
                    'menu' => $item['menu'],
                    'gambar' => null,
                    'status' => 'aktif',
                ]
            );
        }
    }
}
