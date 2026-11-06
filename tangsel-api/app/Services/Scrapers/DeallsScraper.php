<?php

namespace App\Services\Scrapers;

use App\Models\Lowongan;
use App\Models\Sumber;
use App\Services\RobotsTxt;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Pengambil lowongan dari sitemap dan halaman detail Dealls.
 *
 * Dealls tidak menyediakan RSS lowongan, tetapi menyediakan sitemap
 * dinamis yang memuat URL halaman lowongan. URL yang mengandung
 * tangerang-selatan diambil, lalu tiap halaman detail dibaca JSON-LD
 * JobPosting-nya dan disaring berdasarkan tanggal.
 */
class DeallsScraper
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
        $config = (array) config('lowongan.scraping.dealls', []);

        if (! (bool) ($config['aktif'] ?? true)) {
            throw new RuntimeException('Scraper Dealls sedang dinonaktifkan.');
        }

        $sitemapUrl = trim((string) ($config['sitemap_url'] ?? ''));

        if ($sitemapUrl === '') {
            throw new RuntimeException('URL sitemap Dealls belum diatur.');
        }

        $xml = $this->ambilHtml($sitemapUrl, $config);
        $tautan = $this->tautanTangsel($xml);
        $maks = (int) ($config['maks_item'] ?? 40);
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
                Log::info('Dealls: detail lowongan gagal dibaca '.$url.': '.$e->getMessage());
                $dilewati++;

                continue;
            }

            if ($item === null || ! $this->tangsel($item) || ! $this->masihBerlaku($item)) {
                $dilewati++;

                continue;
            }

            $item['sumber'] = (string) ($config['sumber'] ?? 'Dealls');
            $item['sumber_url'] = (string) ($config['sumber_url'] ?? 'https://dealls.com');
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
    private function tautanTangsel(string $xml): array
    {
        $sebelum = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument;
            $terbaca = $dom->loadXML($xml, LIBXML_NONET);
        } catch (Throwable) {
            $terbaca = false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($sebelum);
        }

        if (! $terbaca) {
            throw new RuntimeException('Sitemap Dealls tidak dapat dibaca.');
        }

        $xpath = new DOMXPath($dom);
        $hasil = [];

        foreach ($xpath->query('//*[local-name()="url"]/*[local-name()="loc"]') ?: [] as $node) {
            $loc = trim((string) $node->textContent);

            if ($loc === '' || ! str_contains($loc, '/loker/')) {
                continue;
            }

            if (str_contains($loc, '/en/loker/') || str_contains($loc, '/loker/lokasi/')) {
                continue;
            }

            if (! str_contains(strtolower($loc), 'tangerang-selatan')) {
                continue;
            }

            $hasil[$loc] = true;
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
            ->timeout((int) ($config['timeout'] ?? 30))
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('HTTP '.$response->status().' untuk '.$url);
        }

        return $response->body();
    }
}
