<?php

namespace App\Services;

class BingLinkResolver
{
    /**
     * Ekstraksi URL artikel asli dari link redirect Bing News (§4.1a).
     *
     * Setiap <link> Bing News berbentuk apiclick.aspx dengan parameter
     * url= berisi URL asli dalam bentuk URL-encoded. Link non-Bing
     * dikembalikan apa adanya.
     */
    public function resolve(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'], $parts['query'])) {
            return $url;
        }

        if (! str_contains($parts['host'], 'bing.com') || ! str_contains($parts['path'] ?? '', '/news/apiclick')) {
            return $url;
        }

        parse_str($parts['query'], $query);

        return isset($query['url']) && $query['url'] !== '' ? $query['url'] : $url;
    }
}
