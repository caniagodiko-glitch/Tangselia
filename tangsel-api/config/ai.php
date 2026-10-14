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

    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),

    'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/',

    'temperature' => 0.3,

    'max_output_tokens' => 300,

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
