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
        'Pondok Aren' => ['pondok aren'],
        'Serpong' => ['serpong'],
        'Serpong Utara' => ['serpong utara'],
        'Setu' => ['setu'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Peta Kelurahan
    |--------------------------------------------------------------------------
    |
    | Cadangan ketika judul dan ringkasan hanya menyebut kelurahan atau
    | kawasan. Hanya nama yang cukup khas yang dimasukkan; nama yang juga
    | dipakai daerah lain sengaja dibiarkan supaya kecamatan tidak salah
    | tebak. Kecamatan kosong lebih baik daripada kecamatan yang keliru.
    |
    */

    'peta_kelurahan' => [
        'Ciputat' => [
            'sawah baru', 'sawah lama', 'serua', 'serua indah',
        ],
        'Ciputat Timur' => [
            'cireundeu', 'pondok ranji', 'rempoa', 'rengas',
        ],
        'Pamulang' => [
            'pamulang barat', 'pamulang timur', 'pondok benda',
            'pondok cabe ilir', 'pondok cabe udik', 'pondok cabe',
            'bambu apus', 'benda baru',
        ],
        'Pondok Aren' => [
            'pondok betung', 'pondok jaya', 'pondok kacang barat',
            'pondok kacang timur', 'pondok kacang', 'pondok karya',
            'pondok pucung', 'jurangmangu barat', 'jurangmangu timur',
            'jurangmangu',
        ],
        'Serpong' => [
            'cilenggang', 'lengkong wetan', 'lengkong gudang timur',
            'lengkong gudang', 'rawa buntu', 'rawa mekar jaya', 'ciater',
        ],
        'Serpong Utara' => [
            'pondok jagung timur', 'pondok jagung', 'jelupang',
            'lengkong karya', 'paku jaya', 'pakulonan',
        ],
        'Setu' => [
            'bakti jaya', 'kademangan', 'keranggan', 'muncul',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Penyaring Wilayah Berita
    |--------------------------------------------------------------------------
    |
    | Kueri berita Tangerang Selatan juga mengembalikan berita Kota Tangerang,
    | Kabupaten Tangerang, dan berita nasional karena nama daerahnya mirip.
    | Penyaring memakai penanda wilayah eksplisit, bukan tebakan.
    |
    | - sumber_lokal   : umpan yang kuerinya sudah dibatasi Tangsel.
    | - sumber_nasional: umpan seluruh Indonesia; tanpa penanda Tangsel ditolak.
    | - sumber lain    : dianggap umum; tanpa penanda Tangsel disimpan sebagai
    |                    draft supaya administrator yang memutuskan.
    |
    */

    'filter_wilayah' => [
        'aktif' => true,
        'maks_umur_hari' => 30,
        'sumber_lokal' => [
            'Pemerintah Kota Tangerang Selatan',
            'berita.tangerangselatankota.go.id',
            'Bing News - Tangerang Selatan',
            'Google News - Tangerang Selatan (7 hari)',
            'Google News - Tangerang Selatan (30 hari)',
            'Polres Tangerang Selatan',
        ],
        'sumber_nasional' => [
            'ANTARA News Nasional',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Penanda Wilayah
    |--------------------------------------------------------------------------
    |
    | Dicocokkan sebagai kata utuh, bukan potongan huruf, supaya "setuju"
    | tidak dianggap "Setu" dan "Ciputra" tidak dianggap "Ciputat".
    |
    */

    'penanda_wilayah' => [
        'tangerang selatan',
        'kota tangerang selatan',
        'tangsel',
        'pemkot tangsel',
        'ciputat timur',
        'serpong utara',
        'pondok aren',
        'ciputat',
        'pamulang',
        'serpong',
        'setu',
        'bsd',
        'bintaro',
        'cilenggang',
        'rawa buntu',
        'lengkong karya',
        'paku jaya',
        'pakulonan',
        'jelupang',
        'pondok jagung',
        'pondok cabe',
        'pondok betung',
        'pondok kacang',
        'pondok karya',
        'pondok pucung',
        'jurangmangu',
        'rempoa',
        'cireundeu',
        'pondok ranji',
        'ciater',
    ],

    'penanda_wilayah_lain' => [
        'kabupaten tangerang',
        'pemkab tangerang',
        'kota tangerang',
        'pemkot tangerang',
        'kotatangerang',
        'karawaci',
        'cipondoh',
        'batuceper',
        'ciledug',
        'cikupa',
        'balaraja',
        'kronjo',
        'pagedangan',
        'pasar kemis',
        'pasarkemis',
        'tigaraksa',
        'kelapa dua',
        'legok',
        'cisauk',
        'solear',
        'jambe',
        'sukamulya',
        'gunung kaler',
        'serang',
        'lebak',
        'pandeglang',
        'cilegon',
        'jakarta',
        'depok',
        'bogor',
        'bekasi',
        'bandung',
    ],

];
