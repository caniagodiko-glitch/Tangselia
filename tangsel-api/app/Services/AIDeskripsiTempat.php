<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Perangkai deskripsi kaya memakai Google Gemini (§4.2, paket Gratis).
 *
 * AI hanya merangkai fakta yang sudah tersedia (nama, jenis, kecamatan,
 * alamat, jam, fasilitas, website). Tidak ada fakta baru yang dikarang.
 * Mengembalikan null bila API key kosong atau panggilan gagal, sehingga
 * pemanggil mempertahankan deskripsi template OSM.
 */
class AIDeskripsiTempat
{
    public function rangkai(Model $tempat, array $konteks): ?string
    {
        $prompt = strtr((string) config('ai.prompt_deskripsi'), [
            ':nama' => mb_substr((string) $tempat->nama, 0, 200),
            ':jenis' => (string) ($konteks['jenis'] ?? '-'),
            ':kecamatan' => (string) ($konteks['kecamatan'] ?? '-'),
            ':alamat' => (string) ($tempat->alamat ?? '-'),
            ':jam' => (string) ($tempat->jam_buka_teks ?? '-'),
            ':fasilitas' => $konteks['fasilitas'] ?? '-',
            ':website' => (string) ($tempat->website ?? '-'),
        ]);

        return app(GeminiClient::class)->teks($prompt, [
            'temperature' => 0.4,
            'maxOutputTokens' => 700,
        ], 'deskripsi tempat "'.$tempat->nama.'"');
    }
}
