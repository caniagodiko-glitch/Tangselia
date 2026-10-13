<?php

namespace App\Services;

use Carbon\Carbon;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class FeedParser
{
    public function __construct(
        private BingLinkResolver $bingLinkResolver,
        private GoogleNewsResolver $googleNewsResolver,
    ) {}

    /**
     * Membaca RSS 2.0, Atom, dan RDF menjadi daftar item seragam
     * tanpa paket tambahan, hanya simplexml (§6.5).
     *
     * @return array<int, array{judul: string, link: ?string, pub_date: ?Carbon, ringkasan: string, gambar: ?string, source_name: ?string, source_url: ?string}>
     *
     * @throws RuntimeException ketika isi feed tidak dapat dibaca.
     */
    public function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            $message = $errors !== [] ? trim($errors[0]->message) : 'XML tidak valid';
            throw new RuntimeException('Feed tidak dapat dibaca: '.$message);
        }

        $items = [];

        foreach ($this->rawItems($document) as $raw) {
            $item = $this->normalize($raw);

            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /** @return array<int, SimpleXMLElement> */
    private function rawItems(SimpleXMLElement $document): array
    {
        if (isset($document->channel->item)) {
            return $this->collect($document->channel->item);
        }

        if (isset($document->item)) {
            return $this->collect($document->item);
        }

        if (isset($document->entry)) {
            return $this->collect($document->entry);
        }

        return [];
    }

    /** @return array<int, SimpleXMLElement> */
    private function collect(SimpleXMLElement $nodes): array
    {
        $items = [];

        foreach ($nodes as $node) {
            $items[] = $node;
        }

        return $items;
    }

    /**
     * @return array{judul: string, link: ?string, pub_date: ?Carbon, ringkasan: string, gambar: ?string, source_name: ?string, source_url: ?string}|null
     */
    private function normalize(SimpleXMLElement $item): ?array
    {
        $judul = trim(strip_tags((string) $item->title));

        if ($judul === '') {
            return null;
        }

        $link = $this->resolveLink($item);
        $source = $this->googleNewsResolver->source($item);

        return [
            'judul' => $judul,
            'link' => $this->bingLinkResolver->resolve($link),
            'pub_date' => $this->publishDate($item),
            'ringkasan' => $this->excerpt($item),
            'gambar' => $this->image($item),
            'source_name' => $source['name'],
            'source_url' => $source['url'],
        ];
    }

    private function resolveLink(SimpleXMLElement $item): ?string
    {
        if (! isset($item->link)) {
            return null;
        }

        foreach ($item->link as $link) {
            $candidate = trim((string) ($link['href'] ?? ''));

            if ($candidate === '') {
                $candidate = trim((string) $link);
            }

            $rel = trim((string) ($link['rel'] ?? ''));

            if ($candidate !== '' && ($rel === '' || $rel === 'alternate')) {
                return $candidate;
            }

            if ($candidate !== '') {
                $fallback = $candidate;
            }
        }

        return $fallback ?? null;
    }

    private function publishDate(SimpleXMLElement $item): ?Carbon
    {
        foreach (['pubDate', 'published', 'updated', 'date'] as $tag) {
            $value = isset($item->{$tag}) ? trim((string) $item->{$tag}) : '';

            if ($value === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                continue;
            }
        }

        $dc = $item->children('http://purl.org/dc/elements/1.1/');

        if (isset($dc->date) && trim((string) $dc->date) !== '') {
            try {
                return Carbon::parse((string) $dc->date);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function excerpt(SimpleXMLElement $item): string
    {
        $raw = '';

        foreach (['description', 'summary', 'content'] as $tag) {
            if (isset($item->{$tag})) {
                $value = trim((string) $item->{$tag});

                if ($value !== '') {
                    $raw = $value;
                    break;
                }
            }
        }

        if ($raw === '') {
            $content = $item->children('http://purl.org/rss/1.0/modules/content/');

            if (isset($content->encoded)) {
                $raw = trim((string) $content->encoded);
            }
        }

        $text = $this->clean($raw);

        return mb_substr($text, 0, 800);
    }

    private function image(SimpleXMLElement $item): ?string
    {
        $thumbnail = $item->xpath('.//*[local-name()="thumbnail"]');

        if ($thumbnail !== []) {
            $url = (string) $thumbnail[0]['url'];

            if ($url !== '') {
                return $url;
            }
        }

        if (isset($item->enclosure)) {
            foreach ($item->enclosure as $enclosure) {
                $type = (string) $enclosure['type'];

                if (str_starts_with($type, 'image/')) {
                    return (string) $enclosure['url'];
                }
            }
        }

        foreach ($item->xpath('.//*[local-name()="content"]') ?? [] as $media) {
            $type = (string) $media['type'];
            $url = (string) $media['url'];

            if ($url === '') {
                continue;
            }

            if (str_starts_with($type, 'image/') || preg_match('/\.(jpe?g|png|webp)(\?|$)/i', $url) === 1) {
                return $url;
            }
        }

        return null;
    }

    private function clean(string $html): string
    {
        $text = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
