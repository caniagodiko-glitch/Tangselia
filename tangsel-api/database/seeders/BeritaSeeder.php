<?php

namespace Database\Seeders;

use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Database\Seeder;

class BeritaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Dua berita editorial (ditulis admin) untuk keperluan demo. Berita
     * agregat tidak diisi di sini karena berasal dari `php artisan berita:fetch`.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@tangselia.id')->first();
        $kategoriKota = Kategori::where('slug', 'berita-kota')->first();
        $kecamatanPamulang = Kecamatan::where('slug', 'pamulang')->first();

        $editorial = [
            [
                'slug' => 'tangselia-luncurkan-peta-layanan-warga',
                'judul' => 'TANGSEL LIA Diluncurkan, Peta Layanan Warga Dalam Satu Aplikasi',
                'ringkasan' => 'Platform informasi, wisata, dan aspirasi warga Tangerang Selatan resmi diluncurkan.',
                'konten' => "TANGERANG SELATAN — Platform TANGSEL LIA resmi diluncurkan untuk memudahkan warga mengakses informasi kota.\n\nPlatform ini menghubungkan berita lokal, direktori tempat, fasilitas olahraga, layanan kesehatan, serta informasi lowongan kerja dan magang dalam satu aplikasi.",
                'kategori_id' => $kategoriKota->id,
                'kecamatan_id' => $kecamatanPamulang->id,
            ],
            [
                'slug' => 'layanan-info-lowongan-kerja-ditambahkan',
                'judul' => 'TANGSEL LIA Hadirkan Info Lowongan Kerja dan Magang di Tangsel',
                'ringkasan' => 'Warga kini dapat menemukan lowongan kerja dan magang di wilayah Tangerang Selatan dalam satu aplikasi.',
                'konten' => "Portal lowongan kerja dan magang kini hadir di TANGSEL LIA.\n\nSetiap lowongan diambil dari sumber lowongan terverifikasi, dirangkum otomatis, lalu diperiksa administrator sebelum tayang agar warga tidak keliru melamar.",
                'kategori_id' => $kategoriKota->id,
                'kecamatan_id' => null,
            ],
        ];

        foreach ($editorial as $item) {
            Berita::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'kategori_id' => $item['kategori_id'],
                    'kecamatan_id' => $item['kecamatan_id'],
                    'editor_id' => $admin->id,
                    'tipe' => 'editorial',
                    'judul' => $item['judul'],
                    'ringkasan' => $item['ringkasan'],
                    'diringkas_oleh' => 'editor',
                    'konten' => $item['konten'],
                    'status' => 'published',
                    'published_at' => now()->subDay(),
                ]
            );
        }
    }
}
