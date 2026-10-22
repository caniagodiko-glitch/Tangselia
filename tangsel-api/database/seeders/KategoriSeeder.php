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

            // Scope lowongan — bidang pekerjaan lowongan kerja, dipetakan
            // dari kata kunci oleh LowonganClassifier.
            ['scope' => 'lowongan', 'nama' => 'Teknologi dan Informasi', 'ikon' => null, 'urutan' => 1],
            ['scope' => 'lowongan', 'nama' => 'Administrasi dan Keuangan', 'ikon' => null, 'urutan' => 2],
            ['scope' => 'lowongan', 'nama' => 'Penjualan dan Ritel', 'ikon' => null, 'urutan' => 3],
            ['scope' => 'lowongan', 'nama' => 'Kuliner dan Perhotelan', 'ikon' => null, 'urutan' => 4],
            ['scope' => 'lowongan', 'nama' => 'Pendidikan dan Kesehatan', 'ikon' => null, 'urutan' => 5],
            ['scope' => 'lowongan', 'nama' => 'Lainnya', 'ikon' => null, 'urutan' => 6],
        ];

        foreach ($kategori as $item) {
            Kategori::updateOrCreate(
                ['slug' => Str::slug($item['nama'])],
                $item
            );
        }
    }
}
