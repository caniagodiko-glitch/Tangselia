<?php

namespace App\Services;

use App\Models\Sumber;
use App\Services\Scrapers\GlintsScraper;
use RuntimeException;

/**
 * Penentu scraper yang dipakai untuk satu sumber lowongan.
 *
 * Sumber RSS tetap ditangani FeedParser di LowonganFetcher. Kelas ini hanya
 * menangani sumber yang tidak punya feed dan harus dibaca dari halaman publik.
 */
class LowonganScraper
{
    public function __construct(private GlintsScraper $glints) {}

    /**
     * Ambil lowongan dari sumber scraping yang sesuai.
     *
     * @return array{
     *     items: list<array<string, mixed>>,
     *     item_count: int,
     *     dilewati: int
     * }
     */
    public function fetch(Sumber $sumber): array
    {
        $host = strtolower((string) parse_url((string) $sumber->feed_url, PHP_URL_HOST));

        if (str_contains($host, 'glints.com')) {
            return $this->glints->fetch($sumber);
        }

        throw new RuntimeException(
            'Belum ada scraper untuk sumber '.$sumber->nama.' ('.($host !== '' ? $host : 'tanpa host').').'
        );
    }
}
