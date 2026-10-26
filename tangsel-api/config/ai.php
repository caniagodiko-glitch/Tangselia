<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Gemini API
    |--------------------------------------------------------------------------
    |
    | Kunci API diambil dari environment variable GEMINI_API_KEY dan tidak
    | pernah disimpan di basis data. Bila kosong, seluruh perangkuman
    | otomatis memakai ringkasan bawaan feed (diringkas_oleh = feed).
    |
    */

    'key' => env('GEMINI_API_KEY'),

    'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),

    'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/',

    'temperature' => 0.3,

    'max_output_tokens' => 300,

    /*
    |--------------------------------------------------------------------------
    | Anggaran Berpikir (Thinking Budget)
    |--------------------------------------------------------------------------
    |
    | Model Gemini generasi terbaru "berpikir" sebelum menjawab. Untuk
    | perangkuman dan ekstraksi JSON yang singkat, proses itu hanya
    | memperlambat dan menghabiskan token. Nilai 0 mematikannya; naikkan
    | bila hasil terasa kurang teliti.
    |
    */

    'thinking_budget' => (int) env('GEMINI_THINKING_BUDGET', 0),

    'timeout' => (int) env('GEMINI_TIMEOUT', 45),

    /*
    |--------------------------------------------------------------------------
    | Percobaan Ulang
    |--------------------------------------------------------------------------
    |
    | Layanan Gemini kadang membalas 429/503 saat permintaan sedang padat.
    | Permintaan diulang beberapa kali dengan jeda yang memanjang sebelum
    | menyerah dan memakai fallback.
    |
    */

    'coba_lagi' => (int) env('GEMINI_COBA_LAGI', 3),

    'jeda_coba_lagi' => (int) env('GEMINI_JEDA_COBA_LAGI', 2000),

    /*
    |--------------------------------------------------------------------------
    | Jeda Antarpermintaan
    |--------------------------------------------------------------------------
    |
    | Jumlah milidetik minimum antarpermintaan ke Gemini. Kuota gratis
    | dibatasi per menit, sehingga puluhan permintaan beruntun memicu
    | balasan 429/503. Nilai 2000 berarti paling banyak 30 permintaan
    | per menit.
    |
    */

    'jeda_antar_permintaan' => (int) env('GEMINI_JEDA_ANTAR_PERMINTAAN', 2000),

    /*
    |--------------------------------------------------------------------------
    | Prompt Perangkuman
    |--------------------------------------------------------------------------
    |
    | Placeholder :judul dan :isi digantikan oleh AISummarizer. Prompt
    | berbahasa Indonesia sesuai kebutuhan portal berita lokal.
    |
    */

    'prompt' => 'Anda adalah penyunting berita lokal Kota Tangerang Selatan. '
        ."Ringkas artikel berikut dalam Bahasa Indonesia yang netral, 2\u20133 kalimat "
        .'(maksimal 60 kata), fakta utama saja, tanpa opini, tanpa salam atau penutup. '
        ."\n\nJudul: :judul\n\nIsi artikel:\n:isi",

    /*
    |--------------------------------------------------------------------------
    | Prompt Deskripsi Direktori Tempat
    |--------------------------------------------------------------------------
    |
    | Dipakai AIDeskripsiTempat. AI HANYA merangkai fakta yang disediakan di
    | bawah (dari OpenStreetMap) — dilarang mengarang alamat, telepon, jam,
    | harga, menu, atau fasilitas baru.
    |
    */

    'prompt_deskripsi' => 'Anda penulis deskripsi direktori tempat di Kota Tangerang Selatan '
        .'untuk aplikasi warga. Tulis 2 paragraf Bahasa Indonesia yang mengalir dan ramah, '
        .'(maksimal 110 kata). Gunakan HANYA fakta di bawah ini dan JANGAN mengarang alamat, '
        .'nomor telepon, jam buka, harga, menu, atau fasilitas yang tidak tertulis. Jika suatu '
        .'data kosong, jangan dibahas. Tanpa salam pembuka atau penutup.'
        ."\n\nNama: :nama\nJenis: :jenis\nKecamatan: :kecamatan\nAlamat: :alamat"
        ."\nJam operasional: :jam\nFasilitas: :fasilitas\nWebsite: :website",

];
