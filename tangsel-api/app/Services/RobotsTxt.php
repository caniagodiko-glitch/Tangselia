<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pembaca aturan robots.txt untuk pengambilan halaman pihak ketiga.
 *
 * Berbeda dengan pemeriksa sederhana, kelas ini memahami wildcard (*) dan
 * penanda akhir ($) yang dipakai banyak situs, termasuk Glints. Aturan
 * terpanjang yang cocok dipakai, dan Allow menang bila panjangnya sama.
 */
class RobotsTxt
{
    /**
     * Nama agen pengidentifikasi sesuai etika perayapan.
     */
    private const USER_AGENT = 'TANGSEL-LIA-Bot/1.0 (+https://tangselia.id)';

    /**
     * Aturan per origin dalam satu proses.
     *
     * @var array<string, list<array{type: string, pattern: string}>>
     */
    private array $aturan = [];

    /**
     * Apakah URL boleh diambil menurut robots.txt origin-nya.
     *
     * Kegagalan membaca robots.txt dianggap mengizinkan, mengikuti
     * kebiasaan umum perayap.
     */
    public function allows(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');

        $aturan = $this->aturanUntuk($origin);

        if ($aturan === []) {
            return true;
        }

        $terpilih = null;
        $panjangTerpilih = -1;

        foreach ($aturan as $rule) {
            if ($rule['pattern'] === '' || ! $this->cocok($path, $rule['pattern'])) {
                continue;
            }

            $panjang = strlen($rule['pattern']);

            if ($panjang > $panjangTerpilih || ($panjang === $panjangTerpilih && $rule['type'] === 'allow')) {
                $terpilih = $rule['type'];
                $panjangTerpilih = $panjang;
            }
        }

        return $terpilih !== 'disallow';
    }

    /**
     * @return list<array{type: string, pattern: string}>
     */
    private function aturanUntuk(string $origin): array
    {
        if (array_key_exists($origin, $this->aturan)) {
            return $this->aturan[$origin];
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get($origin.'/robots.txt');
        } catch (Throwable) {
            return $this->aturan[$origin] = [];
        }

        if (! $response->successful()) {
            return $this->aturan[$origin] = [];
        }

        return $this->aturan[$origin] = $this->parse($response->body());
    }

    /**
     * Mengambil aturan yang berlaku untuk agen ini. Grup khusus agen
     * mengalahkan grup wildcard.
     *
     * @return list<array{type: string, pattern: string}>
     */
    private function parse(string $robots): array
    {
        $grup = [];
        $aktif = null;
        $harapAgen = false;

        foreach (preg_split('/\r\n|\r|\n/', $robots) ?: [] as $baris) {
            $baris = trim((string) preg_replace('/#.*$/', '', $baris));

            if ($baris === '') {
                continue;
            }

            [$field, $nilai] = array_pad(explode(':', $baris, 2), 2, '');
            $field = strtolower(trim($field));
            $nilai = trim($nilai);

            if ($field === 'user-agent') {
                if ($aktif === null || ! $harapAgen) {
                    $grup[] = ['agents' => [], 'rules' => []];
                    $aktif = array_key_last($grup);
                }

                $grup[$aktif]['agents'][] = strtolower($nilai);
                $harapAgen = true;

                continue;
            }

            if (in_array($field, ['allow', 'disallow'], true)) {
                if ($aktif !== null) {
                    $grup[$aktif]['rules'][] = ['type' => $field, 'pattern' => $nilai];
                }

                $harapAgen = false;
            }
        }

        $khusus = [];
        $umum = [];

        foreach ($grup as $satu) {
            $untukKami = false;
            $wildcard = false;

            foreach ($satu['agents'] as $agent) {
                if ($agent !== '*' && str_contains($agent, 'tangsel-lia')) {
                    $untukKami = true;
                }

                if ($agent === '*') {
                    $wildcard = true;
                }
            }

            if ($untukKami) {
                $khusus = array_merge($khusus, $satu['rules']);
            } elseif ($wildcard) {
                $umum = array_merge($umum, $satu['rules']);
            }
        }

        return $khusus !== [] ? $khusus : $umum;
    }

    /**
     * Mencocokkan path dengan pola robots.txt yang mendukung * dan $.
     */
    private function cocok(string $path, string $pattern): bool
    {
        $anchored = str_ends_with($pattern, '$');

        if ($anchored) {
            $pattern = substr($pattern, 0, -1);
        }

        $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).($anchored ? '$' : '').'/u';

        return preg_match($regex, $path) === 1;
    }
}
