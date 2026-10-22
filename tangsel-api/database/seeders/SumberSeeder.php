<?php

namespace Database\Seeders;

use App\Models\Sumber;
use Illuminate\Database\Seeder;

class SumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tabel sumber dipakai dua fitur: berita (tipe berita) dan lowongan
     * kerja (tipe lowongan). Sumber berita lolos uji nyata sesuai catatan
     * teknis; sumber lowongan memakai pencarian Google News RSS dengan
     * operator site: karena feed RSS job board langsung membalas 403/404.
     */
    public function run(): void
    {
        $sumber = [
            // Sumber berita.
            [
                'nama' => 'Google News - Tangerang Selatan (7 hari)',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=%22Tangerang+Selatan%22+when%3A7d&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Google News - Tangerang Selatan (30 hari)',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=%22Tangerang+Selatan%22+when%3A30d&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Bing News - Tangerang Selatan',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://www.bing.com/news/search?q=Tangerang+Selatan&format=rss',
            ],
            [
                'nama' => 'ANTARA News Banten',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://banten.antaranews.com/rss/terkini.xml',
            ],
            [
                'nama' => 'TangerangNews',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://www.tangerangnews.com/rss',
            ],
            [
                'nama' => 'BantenNews',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://www.bantennews.co.id/feed',
            ],
            [
                'nama' => 'PosBanten',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://posbanten.com/feed',
            ],
            [
                'nama' => 'SinarBanten',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://www.sinarbanten.com/feed/',
            ],
            [
                'nama' => 'ANTARA News Nasional',
                'tipe' => 'berita',
                'jenis' => 'rss',
                'feed_url' => 'https://www.antaranews.com/rss/terkini.xml',
            ],

            // Sumber lowongan dari Bing News. Bing tidak mendukung operator
            // site: seperti Google News, tetapi tautannya menunjuk langsung ke
            // artikel asal dan cuplikan teksnya berisi ringkasan lowongan yang
            // bisa dibaca.
            [
                'nama' => 'Bing News - Lowongan Kerja Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://www.bing.com/news/search?q=%22lowongan+kerja%22+%22Tangerang+Selatan%22&format=rss',
            ],
            [
                'nama' => 'Bing News - Lowongan Kerja BSD',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://www.bing.com/news/search?q=%22lowongan+kerja%22+BSD&format=rss',
            ],
            [
                'nama' => 'Bing News - Loker Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://www.bing.com/news/search?q=loker+%22Tangerang+Selatan%22&format=rss',
            ],
            [
                'nama' => 'Bing News - Informasi Lowongan Kerja Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://www.bing.com/news/search?q=informasi+lowongan+kerja+Tangerang+Selatan&format=rss',
            ],
            // Sumber lowongan dari job board besar. Pencarian Google News
            // memakai operator site: untuk menjaring halaman lowongan dari job
            // board, dan judulnya memuat pola yang mudah dibaca seperti
            // "Perusahaan hiring Posisi".
            [
                'nama' => 'Lowongan - LinkedIn Jobs (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=site%3Alinkedin.com%2Fjobs+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Lowongan - Glints (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=site%3Aglints.com+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Lowongan - Karir.com (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=site%3Akarir.com+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Lowongan Kerja Umum (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=%22lowongan+kerja%22+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Magang (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=magang+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Lowongan BSD, Bintaro, Serpong, Pamulang',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=%28BSD+OR+Bintaro+OR+Serpong+OR+Pamulang%29+lowongan&hl=id&gl=ID&ceid=ID%3Aid',
            ],

        ];

        foreach ($sumber as $item) {
            Sumber::updateOrCreate(
                ['feed_url' => $item['feed_url']],
                $item + ['aktif' => true]
            );
        }
    }
}
