<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AISummarizer
{
    /**
     * Perangkuman artikel memakai Google Gemini API (§4.2).
     *
     * Mengembalikan null ketika API key kosong, panggilan gagal, atau
     * respons tidak berisi teks — pemanggil yang memutuskan fallback
     * ke ringkasan bawaan feed (diringkas_oleh = feed).
     */
    public function summarize(string $judul, string $isi): ?string
    {
        $key = config('ai.key');

        if (! is_string($key) || $key === '') {
            return null;
        }

        $prompt = strtr((string) config('ai.prompt'), [
            ':judul' => mb_substr($judul, 0, 300),
            ':isi' => mb_substr($isi, 0, 6000),
        ]);

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $key])
                ->timeout(30)
                ->post(config('ai.endpoint').config('ai.model').':generateContent', [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => config('ai.temperature'),
                        'maxOutputTokens' => config('ai.max_output_tokens'),
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('Gemini API tidak terjangkau: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Gemini API mengembalikan status '.$response->status().' untuk "'.$judul.'".');

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
