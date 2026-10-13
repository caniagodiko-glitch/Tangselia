<?php

namespace Database\Seeders;

use App\Models\BeritaSumber;
use Illuminate\Database\Seeder;

class BeritaSumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Sumber lolos uji nyata sesuai §3.1 catatan teknis.
     */
    public function run(): void
    {
        $sumber = [
            [
                'nama' => 'Google News - Tangerang Selatan (7 hari)',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=%22Tangerang+Selatan%22+when%3A7d&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Google News - Tangerang Selatan (30 hari)',
                'jenis' => 'rss',
                'feed_url' => 'https://news.google.com/rss/search?q=%22Tangerang+Selatan%22+when%3A30d&hl=id&gl=ID&ceid=ID%3Aid',
            ],
            [
                'nama' => 'Bing News - Tangerang Selatan',
                'jenis' => 'rss',
                'feed_url' => 'https://www.bing.com/news/search?q=Tangerang+Selatan&format=rss',
            ],
            [
                'nama' => 'ANTARA News Banten',
                'jenis' => 'rss',
                'feed_url' => 'https://banten.antaranews.com/rss/terkini.xml',
            ],
            [
                'nama' => 'TangerangNews',
                'jenis' => 'rss',
                'feed_url' => 'https://www.tangerangnews.com/rss',
            ],
            [
                'nama' => 'BantenNews',
                'jenis' => 'rss',
                'feed_url' => 'https://www.bantennews.co.id/feed',
            ],
            [
                'nama' => 'PosBanten',
                'jenis' => 'rss',
                'feed_url' => 'https://posbanten.com/feed',
            ],
            [
                'nama' => 'SinarBanten',
                'jenis' => 'rss',
                'feed_url' => 'https://www.sinarbanten.com/feed/',
            ],
            [
                'nama' => 'ANTARA News Nasional',
                'jenis' => 'rss',
                'feed_url' => 'https://www.antaranews.com/rss/terkini.xml',
            ],
        ];

        foreach ($sumber as $item) {
            BeritaSumber::updateOrCreate(
                ['feed_url' => $item['feed_url']],
                $item + ['aktif' => true]
            );
        }
    }
}
