<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kategori Bawaan
    |--------------------------------------------------------------------------
    |
    | Slug kategori yang dipakai ketika Categorizer tidak menemukan
    | kecocokan kata kunci apa pun pada judul dan deskripsi berita.
    |
    */

    'kategori_bawaan' => 'berita-kota',

    /*
    |--------------------------------------------------------------------------
    | Peta Kategori (§6.4)
    |--------------------------------------------------------------------------
    |
    | Pemetaan kata kunci deklaratif ke nama kategori berita. Menambah
    | kategori baru cukup menambahkan satu entri di sini.
    |
    */

    'peta_kategori' => [
        'Tata Kota & Lingkungan' => [
            'sampah', 'lingkungan', 'banjir', 'drainase', 'got', 'persampahan',
            'pohon tumbang', 'polusi', 'udara', 'emisi', 'tata kota', 'ruang terbuka',
        ],
        'Ekonomi & UMKM' => [
            'umkm', 'ekonomi', 'pasar', 'dagang', 'usaha', 'inflasi', 'harga',
            'koperasi', 'wirausaha', 'rupiah', 'ekspor',
        ],
        'Transportasi' => [
            'transportasi', 'angkutan', 'angkot', 'busway', 'transjakarta',
            'kemacetan', 'macet', 'lalu lintas', 'terminal', 'stasiun',
            'kereta', 'pesawat', 'bandara', 'ojek', 'jalan rusak',
        ],
        'Pendidikan' => [
            'pendidikan', 'sekolah', 'siswa', 'guru', 'universitas', 'kampus',
            'ujian', 'ppdb', 'beasiswa', 'akademik', 'pelajar',
        ],
        'Kesehatan' => [
            'kesehatan', 'rumah sakit', 'puskesmas', 'dokter', 'vaksin',
            'obat', 'bpjs', 'jkn', 'dbd', 'demam berdarah', 'epidemi',
            'klinik', 'apotek',
        ],
        'Olahraga' => [
            'olahraga', 'sepak bola', 'sepakbola', 'timnas', 'liga',
            'atlet', 'turnamen', 'bulutangkis', 'badminton', 'stadion',
            'futsal', 'renang',
        ],
        'Hiburan' => [
            'hiburan', 'musik', 'film', 'artis', 'selebriti', 'konser',
            'seni', 'budaya', 'festival', 'bioskop', 'wisata', 'pantai',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Peta Kecamatan
    |--------------------------------------------------------------------------
    |
    | Kata kunci (huruf kecil) per kecamatan untuk penentuan lokasi
    | berita agregat. Kunci harus sama persis dengan nama kecamatan.
    |
    */

    'peta_kecamatan' => [
        'Ciputat' => ['ciputat'],
        'Ciputat Timur' => ['ciputat timur'],
        'Pamulang' => ['pamulang'],
        'Pondok Aren' => ['pondok aren', 'bintaro'],
        'Serpong' => ['serpong', 'bsd'],
        'Serpong Utara' => ['serpong utara'],
        'Setu' => ['setu'],
    ],

];
