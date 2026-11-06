<?php

namespace App\Services;

use App\Models\Sumber;
use App\Services\Scrapers\DeallsScraper;
use App\Services\Scrapers\GlintsScraper;
use App\Services\Scrapers\KitalulusScraper;
use RuntimeException;

/**
 * Penentu scraper yang dipakai untuk satu sumber lowongan.
 *
 * Sumber RSS tetap ditangani FeedParser di LowonganFetcher. Kelas ini hanya
 * menangani sumber yang tidak punya feed dan harus dibaca dari halaman publik,
 * yaitu Glints (opsional), Kitalulus, dan Dealls.
 */
class LowonganScraper
{
    public function __construct(
        private GlintsScraper $glints,
        private KitalulusScraper $kitalulus,
        private DeallsScraper $dealls,
    ) {}

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

        if (str_contains($host, 'kitalulus.com')) {
            return $this->kitalulus->fetch($sumber);
        }

        if (str_contains($host, 'dealls.com')) {
            return $this->dealls->fetch($sumber);
        }

        throw new RuntimeException(
            'Belum ada scraper untuk sumber '.$sumber->nama.' ('.($host !== '' ? $host : 'tanpa host').').'
        );
    }
}
