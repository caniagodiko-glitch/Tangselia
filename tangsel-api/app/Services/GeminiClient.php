<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiClient
{
    /**
     * Status HTTP yang menandakan gangguan sesaat dan layak dicoba ulang:
     * kuota terlampaui (429) dan kapasitas penuh (5xx).
     *
     * @var list<int>
     */
    private const COBA_LAGI = [429, 500, 502, 503, 504];

    /**
     * Waktu (microtime) permintaan terakhir yang dikirim, dipakai untuk
     * menjaga jarak antarpermintaan agar tidak menabrak kuota menit.
     */
    private static float $terakhir = 0.0;

    /**
     * Kirim satu permintaan generateContent dan kembalikan teks jawabannya.
     *
     * Seluruh pemanggil AI memakai metode ini agar penanganan kunci kosong,
     * timeout, percobaan ulang, dan penghematan token (thinking budget)
     * hanya ditulis satu kali.
     *
     * @param  array<string, mixed>  $generationConfig
     * @return string|null Null bila kunci kosong, gagal permanen, atau jawaban kosong.
     */
    public function teks(string $prompt, array $generationConfig, string $konteks): ?string
    {
        $key = config('ai.key');

        if (! is_string($key) || $key === '') {
            return null;
        }

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => $generationConfig + [
                'thinkingConfig' => [
                    'thinkingBudget' => (int) config('ai.thinking_budget'),
                ],
            ],
        ];

        $maks = max(1, (int) config('ai.coba_lagi'));

        for ($percobaan = 1; $percobaan <= $maks; $percobaan++) {
            $this->jedaAntarPermintaan();

            try {
                $response = Http::withHeaders(['x-goog-api-key' => $key])
                    ->timeout((int) config('ai.timeout'))
                    ->post(config('ai.endpoint').config('ai.model').':generateContent', $payload);
            } catch (Throwable $e) {
                Log::warning("Gemini tidak terjangkau ({$konteks}), percobaan {$percobaan}: ".$e->getMessage());
                $this->tunggu($percobaan, $maks);

                continue;
            }

            if ($response->successful()) {
                return $this->ambilTeks($response);
            }

            if (! in_array($response->status(), self::COBA_LAGI, true)) {
                Log::warning("Gemini HTTP {$response->status()} ({$konteks}).");

                return null;
            }

            Log::warning("Gemini HTTP {$response->status()} ({$konteks}), percobaan {$percobaan}.");
            $this->tunggu($percobaan, $maks);
        }

        return null;
    }

    /**
     * Ambil teks jawaban, lewati bagian "thinking" bila model menyertakannya.
     */
    private function ambilTeks(Response $response): ?string
    {
        $parts = $response->json('candidates.0.content.parts');

        if (! is_array($parts)) {
            return null;
        }

        $teks = '';

        foreach ($parts as $part) {
            if (! is_array($part) || ($part['thought'] ?? false) === true) {
                continue;
            }

            if (isset($part['text']) && is_string($part['text'])) {
                $teks .= $part['text'];
            }
        }

        $teks = trim($teks);

        return $teks === '' ? null : $teks;
    }

    /**
     * Jaga jarak antarpermintaan agar tidak menabrak batas kuota per menit.
     *
     * Dilewati saat pengujian agar test suite tidak ikut menunggu.
     */
    private function jedaAntarPermintaan(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $jeda = (int) config('ai.jeda_antar_permintaan');

        if ($jeda <= 0) {
            return;
        }

        $sisa = ($jeda / 1000) - (microtime(true) - self::$terakhir);

        if (self::$terakhir > 0.0 && $sisa > 0) {
            usleep((int) ($sisa * 1_000_000));
        }

        self::$terakhir = microtime(true);
    }

    /**
     * Jeda sebelum percobaan berikutnya, memanjang setiap kali gagal.
     *
     * Dilewati saat pengujian agar test suite tidak ikut menunggu.
     */
    private function tunggu(int $percobaan, int $maks): void
    {
        if ($percobaan >= $maks || app()->runningUnitTests()) {
            return;
        }

        $jeda = (int) config('ai.jeda_coba_lagi');

        if ($jeda > 0) {
            usleep($jeda * $percobaan * 1000);
        }
    }
}
