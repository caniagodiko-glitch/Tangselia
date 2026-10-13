<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ArticleContentFetcher
{
    /**
     * Nama agen pengidentifikasi sesuai etika perayapan (§5.2).
     */
    private const USER_AGENT = 'TANGSEL-LIA-Bot/1.0 (+https://tangselia.id)';

    /** @var array<string, bool> cache robots.txt per origin dalam satu proses */
    private array $robotsCache = [];

    /**
     * Mengambil halaman artikel asli sebagai bahan ringkuman AI setelah
     * memeriksa robots.txt (§4.2). Teks hanya dipakai sementara dan tidak
     * disimpan ke basis data.
     *
     * Mengembalikan null ketika halaman tidak boleh diambil, gagal
     * dimuat, atau isinya terlalu tipis.
     */
    public function fetch(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');

        if (! $this->allows($origin, $path)) {
            return null;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(15)
                ->get($url);
        } catch (Throwable $e) {
            Log::info('ArticleContentFetcher gagal memuat '.$url.': '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $text = $this->toText($response->body());

        if (mb_strlen($text) < 120) {
            return null;
        }

        return mb_substr($text, 0, 6000);
    }

    /**
     * Membaca robots.txt origin tersebut dan menentukan apakah path
     * boleh diambil. Kegagalan membaca robots.txt dianggap mengizinkan.
     */
    private function allows(string $origin, string $path): bool
    {
        if (isset($this->robotsCache[$origin])) {
            return $this->robotsCache[$origin];
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get($origin.'/robots.txt');
        } catch (Throwable) {
            return $this->robotsCache[$origin] = true;
        }

        if (! $response->successful()) {
            return $this->robotsCache[$origin] = true;
        }

        return $this->robotsCache[$origin] = $this->evaluate($response->body(), $path);
    }

    /**
     * Evaluasi aturan User-agent: * untuk satu path.
     */
    private function evaluate(string $robots, string $path): bool
    {
        $rules = [];
        $matched = false;
        $currentApplies = false;

        foreach (preg_split('/\r\n|\r|\n/', $robots) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$field, $value] = array_pad(explode(':', $line, 2), 2, '');
            $field = strtolower(trim($field));
            $value = trim($value);

            if ($field === 'user-agent') {
                if ($currentApplies && $rules !== []) {
                    break;
                }

                $currentApplies = $value === '*' || str_contains(strtolower($value), 'tangsel-lia');
                $matched = $matched || $currentApplies;

                continue;
            }

            if ($currentApplies && in_array($field, ['disallow', 'allow'], true)) {
                $rules[] = [$field, $value];
            }
        }

        if ($matched === false || $rules === []) {
            return true;
        }

        $best = null;
        $bestLength = -1;

        foreach ($rules as [$type, $pattern]) {
            if ($pattern === '') {
                continue;
            }

            if (str_starts_with($path, $pattern) && strlen($pattern) > $bestLength) {
                $best = $type;
                $bestLength = strlen($pattern);
            }
        }

        return $best !== 'disallow';
    }

    private function toText(string $html): string
    {
        $text = preg_replace('#<(script|style|noscript|svg)[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $text = preg_replace('#<br\s*/?>|</p>|</h[1-6]>#i', ' ', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
