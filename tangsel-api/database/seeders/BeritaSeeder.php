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
     * Dua berita editorial (ditulis admin) dan tiga agregat (hasil
     * pengambilan + perangkuman AI) untuk keperluan demo dan pengujian.
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
                'konten' => "TANGERANG SELATAN — Platform TANGSEL LIA resmi diluncurkan untuk memudahkan warga mengakses informasi kota.\n\nPlatform ini menghubungkan berita lokal, direktori tempat, fasilitas olahraga, layanan kesehatan, dan kanal aspirasi warga dalam satu aplikasi.",
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

        $agregat = [
            [
                'slug' => 'pemkot-tangsel-perkuat-sistem-drainase',
                'judul' => 'Pemkot Tangsel Perkuat Sistem Drainase Hadapi Musim Hujan',
                'ringkasan' => 'Pemerintah Kota Tangerang Selatan memperkuat saluran drainase di sejumlah titik rawan banjir menjelang musim hujan.',
                'sumber' => 'ANTARA News Banten',
                'sumber_url' => 'https://banten.antaranews.com/',
                'url_asli' => 'https://banten.antaranews.com/berita/contoh-drainase',
                'diringkas_oleh' => 'ai',
                'kecamatan_id' => null,
            ],
            [
                'slug' => 'layanan-antrean-rsud-kini-terintegrasi',
                'judul' => 'Layanan Antrean RSUD Kini Bisa Dipantau Warga',
                'ringkasan' => 'RSUD di Tangerang Selatan menyediakan informasi antrean poli agar warga tidak menunggu lama.',
                'sumber' => 'TangerangNews',
                'sumber_url' => 'https://www.tangerangnews.com/',
                'url_asli' => 'https://www.tangerangnews.com/berita/contoh-antrean',
                'diringkas_oleh' => 'ai',
                'kecamatan_id' => $kecamatanPamulang->id,
            ],
            [
                'slug' => 'festival-budaya-tangsel-penuh-pengunjung',
                'judul' => 'Festival Budaya Tangerang Selatan Diminati Pengunjung',
                'ringkasan' => 'Festival budaya tahunan menarik ribuan pengunjung dari berbagai wilayah.',
                'sumber' => 'PosBanten',
                'sumber_url' => 'https://posbanten.com/',
                'url_asli' => 'https://posbanten.com/contoh-festival',
                'diringkas_oleh' => 'feed',
                'kecamatan_id' => null,
            ],
        ];

        foreach ($agregat as $item) {
            Berita::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'kategori_id' => $kategoriKota->id,
                    'kecamatan_id' => $item['kecamatan_id'],
                    'editor_id' => null,
                    'tipe' => 'agregat',
                    'judul' => $item['judul'],
                    'ringkasan' => $item['ringkasan'],
                    'diringkas_oleh' => $item['diringkas_oleh'],
                    'konten' => null,
                    'url_asli' => $item['url_asli'],
                    'sumber' => $item['sumber'],
                    'sumber_url' => $item['sumber_url'],
                    'status' => 'published',
                    'published_at' => now()->subHours(fake()->numberBetween(2, 20)),
                    'fetched_at' => now()->subHours(fake()->numberBetween(1, 12)),
                ]
            );
        }
    }
}
