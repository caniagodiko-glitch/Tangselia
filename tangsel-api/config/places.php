<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Places API (New)
    |--------------------------------------------------------------------------
    |
    | Kunci API diambil dari environment variable GOOGLE_MAPS_API_KEY dan
    | tidak pernah disimpan di basis data. Bila kosong, rating, ulasan, jam,
    | dan foto tidak ditampilkan (bukan galat) — data inti tetap dari OSM.
    |
    | Catatan kepatuhan: sesuai Ketentuan Layanan Google, kita hanya boleh
    | menyimpan place_id. Rating, ulasan, dan foto diambil saat tampil
    | (live) dan tidak dipersistensi ke basis data.
    |
    */

    'key' => env('GOOGLE_MAPS_API_KEY'),

    'timeout' => 15,

    /*
    |--------------------------------------------------------------------------
    | Endpoint
    |--------------------------------------------------------------------------
    */

    'search_endpoint' => 'https://places.googleapis.com/v1/places:searchText',

    'detail_endpoint' => 'https://places.googleapis.com/v1/places/',

    'photo_endpoint' => 'https://places.googleapis.com/v1/',

    /*
    |--------------------------------------------------------------------------
    | Field Mask
    |--------------------------------------------------------------------------
    |
    | Field mask menentukan kolom yang diminta agar biaya dan ukuran respons
    | tetap efisien.
    |
    */

    'search_field_mask' => implode(',', [
        'places.id',
        'places.displayName',
        'places.formattedAddress',
        'places.location',
    ]),

    'detail_field_mask' => implode(',', [
        'id',
        'displayName',
        'formattedAddress',
        'rating',
        'userRatingCount',
        'regularOpeningHours',
        'currentOpeningHours',
        'internationalPhoneNumber',
        'nationalPhoneNumber',
        'photos',
        'googleMapsUri',
        'location',
        'primaryTypeDisplayName',
    ]),

    /*
    |--------------------------------------------------------------------------
    | Preferensi Pencarian
    |--------------------------------------------------------------------------
    */

    'bahasa' => 'id',

    'region' => 'id',

    'radius_bias_meter' => 8000,

];
