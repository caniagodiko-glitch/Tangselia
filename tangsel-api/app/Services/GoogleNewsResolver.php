<?php

namespace App\Services;

use SimpleXMLElement;

class GoogleNewsResolver
{
    /**
     * Pengambilan atribusi sumber dari elemen <source> Google News (§4.1b).
     *
     * URL artikel asli Google News tidak dapat diteruskan dari sisi server,
     * sehingga yang diandalkan hanya nama dan URL penerbit dari elemen
     * <source url="...">Nama Penerbit</source>.
     *
     * @return array{name: ?string, url: ?string}
     */
    public function source(SimpleXMLElement $item): array
    {
        if (! isset($item->source)) {
            return ['name' => null, 'url' => null];
        }

        $name = trim((string) $item->source);
        $url = (string) $item->source['url'];

        return [
            'name' => $name === '' ? null : $name,
            'url' => $url === '' ? null : $url,
        ];
    }
}
