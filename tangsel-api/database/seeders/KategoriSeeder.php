<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategori = [
            // Scope berita — "8 kategori informasi" sesuai §6.3, dipetakan
            // dari kata kunci oleh Categorizer (§6.4).
            ['scope' => 'berita', 'nama' => 'Berita Kota', 'ikon' => null, 'urutan' => 1],
            ['scope' => 'berita', 'nama' => 'Tata Kota & Lingkungan', 'ikon' => null, 'urutan' => 2],
            ['scope' => 'berita', 'nama' => 'Ekonomi & UMKM', 'ikon' => null, 'urutan' => 3],
            ['scope' => 'berita', 'nama' => 'Transportasi', 'ikon' => null, 'urutan' => 4],
            ['scope' => 'berita', 'nama' => 'Pendidikan', 'ikon' => null, 'urutan' => 5],
            ['scope' => 'berita', 'nama' => 'Kesehatan', 'ikon' => null, 'urutan' => 6],
            ['scope' => 'berita', 'nama' => 'Olahraga', 'ikon' => null, 'urutan' => 7],
            ['scope' => 'berita', 'nama' => 'Hiburan', 'ikon' => null, 'urutan' => 8],

            // Scope laporan — empat opsi form Buat Laporan sesuai mockup.
            ['scope' => 'laporan', 'nama' => 'Jalan Rusak atau Infrastruktur', 'ikon' => '🚧', 'urutan' => 1],
            ['scope' => 'laporan', 'nama' => 'Sampah dan Kebersihan', 'ikon' => '🗑️', 'urutan' => 2],
            ['scope' => 'laporan', 'nama' => 'Fasilitas Umum', 'ikon' => '🏛️', 'urutan' => 3],
            ['scope' => 'laporan', 'nama' => 'Ketertiban dan Keamanan', 'ikon' => '⚠️', 'urutan' => 4],
        ];

        foreach ($kategori as $item) {
            Kategori::updateOrCreate(
                ['slug' => Str::slug($item['nama'])],
                $item
            );
        }
    }
}
