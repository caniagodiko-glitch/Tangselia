<?php

namespace App\Services\Scrapers;

use App\Models\Lowongan;
use App\Models\Sumber;
use App\Services\RobotsTxt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Pengambil lowongan dari halaman lokasi Kitalulus.
 *
 * Halaman lokasi Tangerang Selatan adalah halaman publik yang sudah
 * dibatasi pada wilayah yang dibutuhkan. Setiap tautan detail dibuka
 * untuk mengambil JSON-LD JobPosting, sehingga deskripsi dan link asli
 * tersimpan tanpa bergantung pada kelas CSS.
 */
class KitalulusScraper
{
    public function __construct(
        private RobotsTxt $robots,
        private JobPostingParser $parser,
    ) {}

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     item_count: int,
     *     dilewati: int
     * }
     */
    public function fetch(Sumber $sumber): array
    {
        $config = (array) config('lowongan.scraping.kitalulus', []);

        if (! (bool) ($config['aktif'] ?? true)) {
            throw new RuntimeException('Scraper Kitalulus sedang dinonaktifkan.');
        }

        $listingUrl = trim((string) ($config['listing_url'] ?? ''));

        if ($listingUrl === '') {
            throw new RuntimeException('URL halaman lokasi Kitalulus belum diatur.');
        }

        $html = $this->ambilHtml($listingUrl, $config);
        $tautan = $this->tautanDetail($html, $listingUrl, (string) ($config['base_url'] ?? 'https://www.kitalulus.com'));
        $maks = (int) ($config['maks_item'] ?? 30);
        $jeda = max(0, (int) ($config['jeda_ms'] ?? 800));

        $items = [];
        $dilewati = 0;

        foreach (array_slice($tautan, 0, $maks) as $url) {
            if (Lowongan::where('url_asli', $url)->exists()) {
                $dilewati++;

                continue;
            }

            try {
                $detailHtml = $this->ambilHtml($url, $config);
                $item = $this->parser->parse($detailHtml, $url);
            } catch (Throwable $e) {
                Log::info('Kitalulus: detail lowongan gagal dibaca '.$url.': '.$e->getMessage());
                $dilewati++;

                continue;
            }

            if ($item === null || ! $this->tangsel($item) || ! $this->masihBerlaku($item)) {
                $dilewati++;

                continue;
            }

            $item['sumber'] = (string) ($config['sumber'] ?? 'Kitalulus');
            $item['sumber_url'] = (string) ($config['sumber_url'] ?? 'https://www.kitalulus.com');
            $items[] = $item;

            if ($jeda > 0) {
                usleep($jeda * 1000);
            }
        }

        return [
            'items' => $items,
            'item_count' => count($tautan),
            'dilewati' => $dilewati,
        ];
    }

    /**
     * @return list<string>
     */
    private function tautanDetail(string $html, string $listingUrl, string $baseUrl): array
    {
        $crawler = new Crawler($html, $listingUrl);
        $hasil = [];

        foreach ($crawler->filterXPath('//a[contains(@href, "/lowongan/detail/")]') as $node) {
            $href = html_entity_decode(trim((string) $node->getAttribute('href')), ENT_QUOTES | ENT_HTML5);

            if ($href === '') {
                continue;
            }

            if (str_starts_with($href, '//')) {
                $href = 'https:'.$href;
            } elseif (str_starts_with($href, '/')) {
                $href = rtrim($baseUrl, '/').$href;
            } elseif (! str_starts_with($href, 'http')) {
                $href = rtrim($baseUrl, '/').'/'.ltrim($href, '/');
            }

            $href = (string) preg_replace('/[?#].*$/', '', $href);

            if (! str_contains($href, '/lowongan/detail/')) {
                continue;
            }

            $hasil[$href] = true;
        }

        return array_keys($hasil);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function tangsel(array $item): bool
    {
        $lokasi = strtolower(trim((string) ($item['lokasi'] ?? '')));

        if ($lokasi === '') {
            return true;
        }

        if (preg_match('/\bkota\s+tangerang\b(?!\s+selatan)/u', $lokasi) === 1) {
            return false;
        }

        return str_contains($lokasi, 'tangerang selatan');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function masihBerlaku(array $item): bool
    {
        $maksUmur = (int) config('lowongan.kedaluwarsa_hari', 30);
        $published = $item['published_at'] ?? null;
        $kedaluwarsa = $item['kedaluwarsa_at'] ?? null;

        if ($published instanceof Carbon && $published->lt(now()->subDays($maksUmur))) {
            return false;
        }

        return ! ($kedaluwarsa instanceof Carbon && $kedaluwarsa->lt(now()));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function ambilHtml(string $url, array $config): string
    {
        if (! $this->robots->allows($url)) {
            throw new RuntimeException('robots.txt melarang pengambilan '.$url);
        }

        $response = Http::withHeaders([
            'User-Agent' => (string) ($config['user_agent'] ?? 'TANGSEL-LIA-Bot/1.0 (+https://tangselia.id)'),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
        ])
            ->timeout((int) ($config['timeout'] ?? 20))
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('HTTP '.$response->status().' untuk '.$url);
        }

        return $response->body();
    }
}
