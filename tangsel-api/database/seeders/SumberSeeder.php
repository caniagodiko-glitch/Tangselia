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
     * kerja (tipe lowongan). Sumber berita memakai RSS. Sumber lowongan
     * hanya memakai scraper JSON-LD dari Kitalulus dan Dealls karena
     * hasilnya terstruktur, punya URL asli, dan layak tayang otomatis.
     *
     * Seluruh sumber lowongan berbasis RSS disimpan dalam keadaan tidak
     * aktif sebagai dokumentasi evaluasi. Hasil RSS tidak dipakai lagi
     * karena tautannya sering berupa redirect agregator dan isinya bukan
     * data lowongan terstruktur.
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

            // Sumber lowongan utama: scraper JSON-LD. Kitalulus memakai
            // halaman lokasi, sedangkan Dealls memakai sitemap dinamis yang
            // sudah dibatasi pada Tangerang Selatan.
            [
                'nama' => 'Kitalulus - Lowongan Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'scraping',
                'feed_url' => 'https://www.kitalulus.com/lowongan/in-kota-tangerang-selatan',
            ],
            [
                'nama' => 'Dealls - Lowongan Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'scraping',
                'feed_url' => 'https://dealls.com/sitemap-dynamic.xml',
            ],

            // Sumber lowongan berbasis RSS. Semuanya dinonaktifkan karena
            // tautannya sering berupa redirect agregator dan isinya bukan
            // data lowongan terstruktur, sehingga tidak bisa ditayangkan
            // otomatis. Entri tetap disimpan sebagai dokumentasi evaluasi
            // dan dapat diaktifkan kembali bila diperlukan.
            [
                'nama' => 'Bing News - Lowongan Kerja Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=%22lowongan+kerja%22+%22Tangerang+Selatan%22&format=rss',
            ],
            [
                'nama' => 'Bing News - Lowongan Kerja BSD',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=%22lowongan+kerja%22+BSD&format=rss',
            ],
            [
                'nama' => 'Bing News - Loker Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=loker+%22Tangerang+Selatan%22&format=rss',
            ],
            [
                'nama' => 'Bing News - Informasi Lowongan Kerja Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=informasi+lowongan+kerja+Tangerang+Selatan&format=rss',
            ],
            [
                'nama' => 'Bing News - Magang Tangerang Selatan',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=magang+%22Tangerang+Selatan%22&format=rss',
            ],
            [
                'nama' => 'Bing News - Lowongan Kerja Bintaro',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=%22lowongan+kerja%22+Bintaro&format=rss',
            ],
            [
                'nama' => 'Bing News - Lowongan Kerja Serpong',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=%22lowongan+kerja%22+Serpong&format=rss',
            ],
            [
                'nama' => 'Bing News - Lowongan Kerja Pamulang',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=%22lowongan+kerja%22+Pamulang&format=rss',
            ],
            [
                'nama' => 'Bing News - Loker Tangerang Selatan Terbaru',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://www.bing.com/news/search?q=loker+Tangerang+Selatan&format=rss',
            ],
            [
                'nama' => 'Lowongan - LinkedIn Jobs (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://news.google.com/rss/search?q=site%3Alinkedin.com%2Fjobs+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Lowongan - Glints (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://news.google.com/rss/search?q=site%3Aglints.com+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Lowongan - Karir.com (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://news.google.com/rss/search?q=site%3Akarir.com+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Lowongan Kerja Umum (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://news.google.com/rss/search?q=%22lowongan+kerja%22+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Magang (Tangerang Selatan)',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
                'feed_url' => 'https://news.google.com/rss/search?q=magang+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Lowongan BSD, Bintaro, Serpong, Pamulang',
                'tipe' => 'lowongan',
                'jenis' => 'rss',
                'aktif' => false,
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
