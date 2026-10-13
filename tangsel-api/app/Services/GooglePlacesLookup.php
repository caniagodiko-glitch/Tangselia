<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GooglePlacesLookup
{
    /**
     * Klien Google Places API (New) untuk data live (rating, ulasan, jam,
     * dan foto). Hanya place_id yang boleh disimpan; seluruh data lain
     * diambil saat tampil dan tidak dipersistensi.
     */
    public function tersedia(): bool
    {
        $key = config('places.key');

        return is_string($key) && $key !== '';
    }

    /**
     * Mencari place_id Google untuk sebuah tempat berdasarkan nama dan
     * koordinat OSM.
     */
    public function cari(string $nama, float $lat, float $lng): ?string
    {
        if (! $this->tersedia()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => config('places.key'),
                'X-Goog-FieldMask' => config('places.search_field_mask'),
            ])
                ->timeout((int) config('places.timeout', 15))
                ->post(config('places.search_endpoint'), [
                    'textQuery' => trim($nama.' '.config('osm.kota')),
                    'languageCode' => config('places.bahasa'),
                    'regionCode' => config('places.region'),
                    'locationBias' => [
                        'circle' => [
                            'center' => ['latitude' => $lat, 'longitude' => $lng],
                            'radius' => (float) config('places.radius_bias_meter'),
                        ],
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('GooglePlacesLookup::cari tidak terjangkau: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('GooglePlacesLookup::cari mengembalikan HTTP '.$response->status().' untuk "'.$nama.'".');

            return null;
        }

        $id = $response->json('places.0.id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Mengambil detail live sebuah place_id dalam bentuk yang siap
     * ditampilkan. Mengembalikan null bila API key kosong atau panggilan
     * gagal (pemanggil menampilkan data OSM saja tanpa galat).
     *
     * @return array{
     *     place_id: string,
     *     nama: string|null,
     *     alamat: string|null,
     *     rating: float|null,
     *     jumlah_ulasan: int|null,
     *     jam: list<string>,
     *     buka_sekarang: bool|null,
     *     telepon: string|null,
     *     foto: list<string>,
     *     peta_url: string|null,
     *     tipe: string|null
     * }|null
     */
    public function detail(string $placeId): ?array
    {
        if (! $this->tersedia()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => config('places.key'),
                'X-Goog-FieldMask' => config('places.detail_field_mask'),
            ])
                ->timeout((int) config('places.timeout', 15))
                ->get(config('places.detail_endpoint').$placeId);
        } catch (Throwable $e) {
            Log::warning('GooglePlacesLookup::detail tidak terjangkau: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('GooglePlacesLookup::detail mengembalikan HTTP '.$response->status().' untuk '.$placeId.'.');

            return null;
        }

        $jam = $response->json('regularOpeningHours.weekdayDescriptions')
            ?? $response->json('currentOpeningHours.weekdayDescriptions')
            ?? [];

        $foto = [];

        foreach ((array) $response->json('photos', []) as $photo) {
            if (is_array($photo) && isset($photo['name']) && is_string($photo['name'])) {
                $foto[] = $photo['name'];
            }
        }

        return [
            'place_id' => (string) ($response->json('id') ?? $placeId),
            'nama' => $response->json('displayName.text'),
            'alamat' => $response->json('formattedAddress'),
            'rating' => is_numeric($response->json('rating')) ? (float) $response->json('rating') : null,
            'jumlah_ulasan' => is_numeric($response->json('userRatingCount')) ? (int) $response->json('userRatingCount') : null,
            'jam' => array_values(array_filter((array) $jam, 'is_string')),
            'buka_sekarang' => is_bool($response->json('currentOpeningHours.openNow')) ? $response->json('currentOpeningHours.openNow') : null,
            'telepon' => $response->json('nationalPhoneNumber') ?? $response->json('internationalPhoneNumber'),
            'foto' => $foto,
            'peta_url' => $response->json('googleMapsUri'),
            'tipe' => $response->json('primaryTypeDisplayName.text'),
        ];
    }

    /**
     * Mengambil berkas foto Google lewat proxy agar API key tidak bocor ke
     * peramban. Mengembalikan isi berkas beserta tipe kontennya.
     *
     * @return array{body: string, content_type: string}|null
     */
    public function foto(string $photoName, int $maxWidth = 1200): ?array
    {
        if (! $this->tersedia() || ! str_starts_with($photoName, 'places/')) {
            return null;
        }

        try {
            $response = Http::withHeaders(['X-Goog-Api-Key' => config('places.key')])
                ->timeout((int) config('places.timeout', 15))
                ->get(config('places.photo_endpoint').$photoName.'/media', [
                    'maxWidthPx' => $maxWidth,
                ]);
        } catch (Throwable $e) {
            Log::warning('GooglePlacesLookup::foto tidak terjangkau: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return [
            'body' => $response->body(),
            'content_type' => $response->header('Content-Type') ?: 'image/jpeg',
        ];
    }
}
