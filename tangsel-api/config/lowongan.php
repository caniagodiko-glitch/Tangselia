<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Umur Lowongan
    |--------------------------------------------------------------------------
    |
    | Lowongan yang sudah tayang otomatis kedaluwarsa setelah jumlah hari
    | ini. Masa berlaku mengikuti kebiasaan lowongan berbulan-bulan, bukan
    | bertahun-tahun, dan mencegah portal menampilkan lowongan basi.
    |
    */

    'kedaluwarsa_hari' => 30,

    /*
    |--------------------------------------------------------------------------
    | Prefix Kode Lowongan
    |--------------------------------------------------------------------------
    |
    | Kode lowongan memakai prefix dan nomor urut enam digit, misalnya
    | LGN-000123. Format ini sama rapinya dengan kode laporan warga
    | sebelumnya agar mudah diingat dan dicari pengguna.
    |
    */

    'kode_prefix' => 'LGN',

    /*
    |--------------------------------------------------------------------------
    | Batas Item per Sumber
    |--------------------------------------------------------------------------
    |
    | Agar beban dan lamanya proses tetap terkendali, tiap sumber hanya
    | boleh menyumbang sejumlah item dalam satu kali jalan.
    |
    */

    'maks_item_per_sumber' => 20,

    /*
    |--------------------------------------------------------------------------
    | Ambil Isi Artikel
    |--------------------------------------------------------------------------
    |
    | Isi artikel diambil sebagai bahan baca AI. Pengambilan tetap memperhatikan
    | robots.txt melalui ArticleContentFetcher dan teksnya tidak pernah
    | disimpan utuh ke dalam basis data.
    |
    | Deskripsi tanpa AI memakai cuplikan ringkasan milik penerbit
    | karena teks halaman masih memuat menu navigasi situs.
    |
    */

    'ambil_isi_artikel' => true,

    /*
    |--------------------------------------------------------------------------
    | Panjang Deskripsi
    |--------------------------------------------------------------------------
    |
    | Batas karakter deskripsi yang disimpan per lowongan.
    |
    */

    'panjang_deskripsi' => 700,

    /*
    |--------------------------------------------------------------------------
    | Prompt Ekstraksi Lowongan
    |--------------------------------------------------------------------------
    |
    | Dipakai AIEkstraksiLowongan. AI HANYA memindahkan fakta yang sudah
    | tertulis pada judul dan isi artikel ke dalam kolom terstruktur.
    | AI dilarang mengarang nama perusahaan, posisi, kualifikasi, atau
    | cara melamar yang tidak disebutkan sumber. Field yang tidak
    | ditemukan dikembalikan kosong, bukan ditebak.
    |
    | Jenis lowongan tidak diminta ke AI karena penentuannya memakai peta
    | kata kunci pada config/lowongan.php yang lebih andal.
    |
    */

    'prompt_ekstraksi' => 'Anda memparsing lowongan kerja dari artikel berita. '
        .'Keluarkan JSON dengan kunci: posisi, perusahaan, deskripsi, '
        .'kualifikasi, cara_apply. '
        .'HANYA ambil fakta yang tertulis pada teks. Bila suatu informasi tidak '
        .'ada, isi dengan string kosong. Jangan mengarang, jangan menambahkan '
        .'informasi dari luar teks. '
        .'Abaikan menu navigasi, nama situs, dan teks promosi yang bukan bagian '
        .'dari lowongan. '
        ."\n\nJudul: :judul\n\nIsi artikel:\n:isi",

    /*
    |--------------------------------------------------------------------------
    | Peta Kategori Lowongan
    |--------------------------------------------------------------------------
    |
    | Kategori lowongan memakai scope lowongan pada tabel kategori dan
    | memetakan kata kunci ke bidang pekerjaan. Peta disimpan di sini
    | supaya menambah bidang baru cukup satu entri.
    |
    */

    'peta_kategori' => [
        'Teknologi dan Informasi' => [
            'software', 'developer', 'programmer', 'teknologi', 'data',
            'analis', 'designer', 'sistem', 'jaringan', 'digital', 'coding',
            'engineer', 'machine learning', 'cyber', 'sistem informasi',
            'it support', 'it tech', 'tech support', 'integration', 'ui ux',
            'front end', 'back end', 'devops', 'quality assurance',
        ],
        'Administrasi dan Keuangan' => [
            'administrasi', 'admin', 'staf', 'staff', 'office', 'kantor',
            'keuangan', 'finance', 'akuntansi', 'accounting', 'hrd', 'sdm',
            'legal', 'hukum', 'sekretaris', 'secretary', 'purchasing',
            'pembelian', 'payroll', 'general affair', 'litigation', 'pajak',
            'tax', 'credit analyst', 'people analytics', 'reward',
            'spv', 'koordinator', 'coordinator', 'konsultan', 'consultant',
        ],
        'Penjualan dan Ritel' => [
            'sales', 'marketing', 'pemasaran', 'kasir', 'cashier', 'retail',
            'toko', 'store', 'promosi', 'promo', 'telemarketing', 'supervisor',
            'area', 'sales executive', 'spg', 'branch manager', 'outlet',
            'property', 'in house', 'usher', 'merchandising',
        ],
        'Kuliner dan Perhotelan' => [
            'kafe', 'cafe', 'coffee', 'resto', 'restaurant', 'barista', 'koki',
            'chef', 'pelayan', 'waiter', 'waitress', 'hotel', 'hospitality',
            'kuliner', 'catering', 'bistro', 'pabrik', 'production', 'kitchen',
            'restoran', 'gelato', 'bakso', 'pastry', 'bakery', 'patisserie',
            'caffe', 'ice cream', 'f&B',
        ],
        'Pendidikan dan Kesehatan' => [
            'guru', 'dosen', 'pengajar', 'instruktur', 'tutor', 'klinik',
            'dokter', 'perawat', 'bidan', 'farmasi', 'apotek', 'psikolog',
            'training', 'pelatihan', 'teacher', 'school', 'sekolah',
            'pendidikan', 'education', 'montessori', 'apoteker',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kategori Bawaan
    |--------------------------------------------------------------------------
    |
    | Slug kategori yang dipakai ketika tidak ada kata kunci yang cocok pada
    | judul dan deskripsi lowongan.
    |
    */

    'kategori_bawaan' => 'lainnya',

    /*
    |--------------------------------------------------------------------------
    | Kata Kunci Jenis Lowongan
    |--------------------------------------------------------------------------
    |
    | Kata kunci yang menentukan lowongan termasuk magang, paruh waktu, atau
    | karier penuh. Dipakai sebagai cadangan ketika AI tidak tersedia.
    |
    */

    'peta_jenis' => [
        'magang' => [
            'magang', 'internship', 'intern', 'trainee', 'magangan', 'praktik',
            'stagiaire', 'kkn', 'skripsi', 'sales trainee',
        ],
        'part-time' => [
            'part time', 'part-time', 'parttime', 'freelance', 'freelancer',
            'sessional', 'gig', 'ojek', 'harian',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kata Kunci Lowongan Kerja
    |--------------------------------------------------------------------------
    |
    | Penanda kuat bahwa sebuah judul benar-benar Lowongan kerja. Dipakai
    | untuk menandai kolom relevan. Sumber job board selalu dianggap
    | relevan karena isinya lowonganutuh, sedangkan pencarian umum hanya
    | ditandai bila judulnya menyebut lowongan secara eksplisit.
    |
    */

    'kata_kunci_lowongan' => [
        'lowongan kerja', 'lowongan', 'loker', 'hiring', 'membuka lowongan',
        'buka lowongan', 'rekrutmen', 'rekrut', 'magang', 'karier', 'karir',
        'career', 'jobs', 'job opening', 'pelamar', 'penempatan', 'trainee',
        'part time', 'freelance', 'lowongan kerja', 'pencari kerja',
    ],

    /*
    |--------------------------------------------------------------------------
    | Penanda Wilayah
    |--------------------------------------------------------------------------
    |
    | Kueri lowongan di Tangerang Selatan juga mengembalikan berita lowongan
    | dari Kota Tangerang karena keduanya berbagi nama daerah. Penanda di
    | bawah dipakai untuk menyaring berita yang jelas berada di luar
    | wilayah, sedangkan sumber job board dipakai apa adanya karena kueri
    | yang dipakai sudah dibatasi pada wilayah Tangsel.
    |
    */

    'penanda_wilayah' => [
        'tangerang selatan',
        'tangsel',
        'bsd',
        'ciputat',
        'pamulang',
        'serpong',
        'setu',
        'cisauk',
        'legok',
        'curug',
    ],

    'penanda_wilayah_lain' => [
        'kota tangerang',
        'pemkot tangerang',
        'kotatangerang',
        'jakarta',
        'bandung',
        'surabaya',
        'medan',
        'makassar',
        'semarang',
        'yogyakarta',
        'denpasar',
        'samarinda',
        'balikpapan',
        'pekanbaru',
        'padang',
        'manado',
        'palembang',
        'banjarmasin',
        'pontianak',
        'serang',
        'bogor',
        'bekasi',
        'depok',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kata Keterangan
    |--------------------------------------------------------------------------
    |
    | Kata yang menandai sebuah potongan judul sebagai kalimat berita, bukan
    | nama posisi atau nama perusahaan. Potongan yang memuat kata ini
    | dibuang supaya kolom posisi dan perusahaan tidak menyimpan kalimat
    | judul yang tidak informatif.
    |
    */

    'kata_keterangan' => [
        'lowongan', 'loker', 'kerja', 'job fair', 'jobfest', 'rekrutmen',
        'rekrut', 'posisi', 'syarat', 'cek', 'terbaru', 'info', 'daftar',
        'pelamar', 'pencari kerja', 'penempatan', 'perusahaan', 'banyak',
        'masih', 'terbuka', 'dibuka', 'cukup', 'sampai', 'hingga',
        'berbagai', 'jenis', 'kota',
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Board pada Query RSS
    |--------------------------------------------------------------------------
    |
    | Query pencarian yang memakai operator site: pada domain job board.
    | Item dari sumber seperti ini pasti lowongan utuh, sehingga selalu
    | ditandai relevan tanpa perlu memeriksa kata kunci judul.
    |
    */

    'domain_job_board' => [
        'linkedin.com/jobs', 'glints.com', 'karir.com', 'jobstreet',
        'indeed.com', 'jobs.co.id', 'loker.id', 'jobsdb',
    ],

];
