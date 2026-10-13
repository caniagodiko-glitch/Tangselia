<?php

use App\Models\FasilitasOlahraga;
use App\Models\LayananKesehatan;
use App\Models\Tempat;

return [

    /*
    |--------------------------------------------------------------------------
    | Endpoint Overpass API
    |--------------------------------------------------------------------------
    |
    | Server Overpass publik sering menolak atau timeout saat sibuk. Importer
    | mencoba tiap endpoint berurutan sampai salah satunya berhasil.
    |
    */

    'endpoints' => [
        'https://overpass-api.de/api/interpreter',
        'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
        'https://overpass.private.coffee/api/interpreter',
    ],

    /*
    |--------------------------------------------------------------------------
    | Identitas & Timeout
    |--------------------------------------------------------------------------
    */

    'user_agent' => 'TANGSEL-LIA-Bot/1.0 (+https://tangselia.id)',

    'timeout' => 90,

    'percobaan_per_endpoint' => 2,

    /*
    |--------------------------------------------------------------------------
    | Wilayah Kota Tangerang Selatan
    |--------------------------------------------------------------------------
    |
    | Kotak batas (bounding box) dari relation OSM 7641582 dalam urutan
    | [selatan, barat, utara, timur]. Semua query dibatasi ke area ini.
    |
    */

    'bbox' => [-6.3622632, 106.6366233, -6.2289594, 106.7800127],

    'kota' => 'Kota Tangerang Selatan',

    /*
    |--------------------------------------------------------------------------
    | Titik Pusat Kecamatan
    |--------------------------------------------------------------------------
    |
    | Batas kecamatan tidak tersedia di OSM, sehingga kecamatan ditentukan
    | dari centroid terdekat (haversine). Koordinat perkiraan dan dapat
    | disempurnakan lewat admin bila perlu.
    |
    */

    'kecamatan_centroid' => [
        'Ciputat' => [-6.3110000, 106.7460000],
        'Ciputat Timur' => [-6.3060000, 106.7700000],
        'Pamulang' => [-6.3439000, 106.7398000],
        'Pondok Aren' => [-6.2740000, 106.7050000],
        'Serpong' => [-6.2827000, 106.6652000],
        'Serpong Utara' => [-6.2455000, 106.6683000],
        'Setu' => [-6.3056000, 106.6208000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemetaan Tag OSM ke Fasilitas
    |--------------------------------------------------------------------------
    |
    | Tag yang bernilai positif akan diterjemahkan menjadi daftar fasilitas
    | pada kolom JSON `fasilitas`.
    |
    */

    'peta_fasilitas' => [
        'internet_access' => 'Wi-Fi',
        'air_conditioning' => 'AC',
        'wheelchair' => 'Akses kursi roda',
        'outdoor_seating' => 'Area luar',
        'takeaway' => 'Bawa pulang',
        'delivery' => 'Antar',
        'smoking' => 'Area merokok',
        'vegetarian' => 'Menu vegetarian',
        'payment:cash' => 'Pembayaran tunai',
        'payment:debit_cards' => 'Kartu debit',
        'payment:credit_cards' => 'Kartu kredit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Definisi Fitur Direktori
    |--------------------------------------------------------------------------
    |
    | Setiap fitur memetakan satu model dengan daftar aturan tag OSM. Aturan
    | pertama yang cocok menentukan nilai kolom `jenis`.
    |
    */

    'fitur' => [
        'tempat' => [
            'label' => 'Kuliner',
            'model' => Tempat::class,
            'tags' => [
                ['jenis' => 'kafe', 'match' => ['amenity' => 'cafe']],
                ['jenis' => 'restoran', 'match' => ['amenity' => 'restaurant']],
                ['jenis' => 'warkop', 'match' => ['amenity' => 'fast_food']],
            ],
        ],

        'olahraga' => [
            'label' => 'Olahraga',
            'model' => FasilitasOlahraga::class,
            'tags' => [
                ['jenis' => 'gym', 'match' => ['leisure' => 'fitness_centre']],
                ['jenis' => 'kolam_renang', 'match' => ['leisure' => 'swimming_pool']],
                ['jenis' => 'lapangan_futsal', 'match' => ['leisure' => 'pitch', 'sport' => 'futsal']],
                ['jenis' => 'lapangan_basket', 'match' => ['leisure' => 'pitch', 'sport' => 'basketball']],
                ['jenis' => 'gor_bulutangkis', 'match' => ['leisure' => 'sports_centre', 'sport' => 'badminton']],
            ],
        ],

        'kesehatan' => [
            'label' => 'Kesehatan',
            'model' => LayananKesehatan::class,
            'tags' => [
                ['jenis' => 'rumah_sakit', 'match' => ['amenity' => 'hospital']],
                ['jenis' => 'klinik', 'match' => ['amenity' => 'clinic']],
                ['jenis' => 'klinik', 'match' => ['amenity' => 'doctors']],
                ['jenis' => 'apotek', 'match' => ['amenity' => 'pharmacy']],
            ],
            'override_nama' => [
                'puskesmas' => 'puskesmas',
                'posyandu' => 'posyandu',
            ],
        ],
    ],

];
