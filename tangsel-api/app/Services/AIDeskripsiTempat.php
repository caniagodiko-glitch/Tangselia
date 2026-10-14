<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        $key = config('ai.key');

        if (! is_string($key) || $key === '') {
            return null;
        }

        $prompt = strtr((string) config('ai.prompt_deskripsi'), [
            ':nama' => mb_substr((string) $tempat->nama, 0, 200),
            ':jenis' => (string) ($konteks['jenis'] ?? '-'),
            ':kecamatan' => (string) ($konteks['kecamatan'] ?? '-'),
            ':alamat' => (string) ($tempat->alamat ?? '-'),
            ':jam' => (string) ($tempat->jam_buka_teks ?? '-'),
            ':fasilitas' => $konteks['fasilitas'] ?? '-',
            ':website' => (string) ($tempat->website ?? '-'),
        ]);

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $key])
                ->timeout(30)
                ->post(config('ai.endpoint').config('ai.model').':generateContent', [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 700,
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('AIDeskripsiTempat: Gemini tidak terjangkau — '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('AIDeskripsiTempat: Gemini HTTP '.$response->status().' untuk "'.$tempat->nama.'".');

            return null;
        }

        $teks = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($teks)) {
            return null;
        }

        $teks = trim($teks);

        return $teks === '' ? null : $teks;
    }
}
