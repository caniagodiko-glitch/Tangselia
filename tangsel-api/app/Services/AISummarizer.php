<?php

namespace App\Services;

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
        $prompt = strtr((string) config('ai.prompt'), [
            ':judul' => mb_substr($judul, 0, 300),
            ':isi' => mb_substr($isi, 0, 6000),
        ]);

        return app(GeminiClient::class)->teks($prompt, [
            'temperature' => config('ai.temperature'),
            'maxOutputTokens' => config('ai.max_output_tokens'),
        ], 'ringkasan berita');
    }
}
