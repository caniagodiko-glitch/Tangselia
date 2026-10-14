<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pelengkap direktori dari situs resmi bisnis (tag `website` OSM).
 *
 * Menghormati robots.txt, hanya mengunduh satu foto sampul (og:image),
 * menyimpan foto secara lokal, dan mencoba membaca menu dari halaman menu
 * statis. Semua hasil diberi atribusi tautan sumber. Situs media sosial dan
 * agregator dilewati seluruhnya.
 */
class InfoDirektoriFetcher
{
    /**
     * Host yang bukan situs resmi bisnis — dilewati.
     *
     * @var list<string>
     */
    private const HOST_TERLARANG = [
        'instagram.com', 'facebook.com', 'm.facebook.com', 'youtube.com',
        'youtu.be', 'twitter.com', 'x.com', 'tiktok.com', 'wa.me', 'wa.link',
        'maps.google.com', 'goo.gl', 'g.page', 'bit.ly', 'linkedin.com',
        'foursquare.com', 'tripadvisor.com', 'spotify.com', 'shopee.co.id',
    ];

    /** @var array<string, list<string>> */
    private array $robotsDisallow = [];

    /** @var array<string, bool> */
    private array $robotsDikenal = [];

    /**
     * @return array{diperiksa: int, dilewati: int, foto: int, deskripsi: int, menu: int, gagal: int}
     */
    public function jalankan(?string $fitur = null, int $maks = 25): array
    {
        $stats = ['diperiksa' => 0, 'dilewati' => 0, 'foto' => 0, 'deskripsi' => 0, 'menu' => 0, 'gagal' => 0];

        foreach ((array) config('osm.fitur') as $kunci => $definisi) {
            if ($fitur !== null && $fitur !== '' && $kunci !== $fitur) {
                continue;
            }

            $modelClass = $definisi['model'];

            $modelClass::whereNotNull('website')
                ->whereNull('info_diunduh_pada')
                ->orderBy('id')
                ->limit($maks)
                ->get()
                ->each(function (Model $tempat) use (&$stats): void {
                    $this->prosesSatu($tempat, $stats);
                });
        }

        return $stats;
    }

    /**
     * @param  array{diperiksa: int, dilewati: int, foto: int, deskripsi: int, menu: int, gagal: int}  $stats
     */
    private function prosesSatu(Model $tempat, array &$stats): void
    {
        $stats['diperiksa']++;

        $url = $this->normalisasiUrl((string) $tempat->website);

        if ($url === null || $this->hostTerlarang($url)) {
            $this->tandaiSelesai($tempat);

            $stats['dilewati']++;

            return;
        }

        if (! $this->diizinkanRobots($url)) {
            $this->tandaiSelesai($tempat);

            $stats['dilewati']++;

            return;
        }

        $this->jeda();

        $halaman = $this->muatHalaman($url);

        if ($halaman === null) {
            $this->tandaiSelesai($tempat);

            $stats['gagal']++;

            return;
        }

        $dom = $this->muatDom($halaman);
        $diubah = false;

        if ($dom !== null) {
            $xpath = new DOMXPath($dom);

            if (($tempat->deskripsi_sumber ?? 'osm') === 'osm') {
                $deskripsi = $this->ambilDeskripsi($xpath);

                if ($deskripsi !== null && mb_strlen($deskripsi) >= 30) {
                    $tempat->deskripsi = mb_substr($deskripsi, 0, 500)
                        ."\n\n(Sumber: situs resmi {$this->host($url)})";
                    $tempat->deskripsi_sumber = 'website';
                    $tempat->atribusi = $this->ejaAtribusi($tempat->atribusi, [
                        'jenis' => 'deskripsi',
                        'sumber' => $this->host($url),
                        'tautan' => $url,
                        'peran' => 'deskripsi dari situs resmi',
                    ]);

                    $stats['deskripsi']++;

                    $diubah = true;
                }
            }

            if ($this->kosong($tempat->gambar)) {
                $gambar = $this->ambilGambar($xpath, $url);

                if ($gambar !== null && ($disimpan = $this->simpanGambar($tempat, $gambar)) !== null) {
                    $tempat->gambar = $disimpan;
                    $tempat->atribusi = $this->ejaAtribusi($tempat->atribusi, [
                        'jenis' => 'foto',
                        'sumber' => $this->host($url),
                        'tautan' => $gambar,
                        'peran' => 'foto sampul dari situs resmi',
                    ]);

                    $stats['foto']++;

                    $diubah = true;
                }
            }
        }

        if ($this->kosong($tempat->menu)) {
            $menu = $this->carikanMenu($url, $halaman);

            if ($menu !== []) {
                $tempat->menu = $menu;
                $tempat->atribusi = $this->ejaAtribusi($tempat->atribusi, [
                    'jenis' => 'menu',
                    'sumber' => $this->host($url),
                    'tautan' => $url,
                    'peran' => 'daftar menu dari situs resmi (terbaik-mungkin)',
                ]);

                $stats['menu']++;

                $diubah = true;
            }
        }

        $tempat->info_diunduh_pada = now();
        $tempat->save();
    }

    private function normalisasiUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.$url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }

    private function host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }

    private function hostTerlarang(string $url): bool
    {
        $host = $this->host($url);

        foreach (self::HOST_TERLARANG as $terlarang) {
            if ($host === $terlarang || str_ends_with($host, '.'.$terlarang)) {
                return true;
            }
        }

        return false;
    }

    private function diizinkanRobots(string $url): bool
    {
        $host = $this->host($url);

        if (isset($this->robotsDikenal[$host])) {
            return $this->periksaDisallow($this->robotsDisallow[$host] ?? [], $url);
        }

        $origin = parse_url($url, PHP_URL_SCHEME).'://'.$host;

        $this->robotsDikenal[$host] = true;
        $this->robotsDisallow[$host] = $this->muatAturanRobots($origin);

        return $this->periksaDisallow($this->robotsDisallow[$host], $url);
    }

    /**
     * @return list<string>
     */
    private function muatAturanRobots(string $origin): array
    {
        $this->jeda();

        try {
            $respons = Http::withHeaders(['User-Agent' => config('osm.user_agent')])
                ->timeout(10)
                ->get($origin.'/robots.txt');
        } catch (Throwable $e) {
            return [];
        }

        if (! $respons->successful()) {
            return [];
        }

        $blok = false;
        $disallow = [];

        foreach (preg_split('/\R/', $respons->body()) as $baris) {
            $baris = trim($baris);

            if ($baris === '' || str_starts_with($baris, '#') || str_starts_with($baris, 'Sitemap:')) {
                continue;
            }

            if (preg_match('/^User-agent:\s*(.+)$/i', $baris, $cocok) === 1) {
                $blok = strtolower(trim($cocok[1])) === '*';

                continue;
            }

            if ($blok && preg_match('/^Disallow:\s*(.*)$/i', $baris, $cocok) === 1) {
                $path = trim($cocok[1]);

                if ($path !== '') {
                    $disallow[] = $path;
                }
            }

            if ($blok && preg_match('/^Allow:\s*(.*)$/i', $baris, $cocok) === 1 && trim($cocok[1]) !== '') {
                $disallow = array_values(array_filter($disallow, fn (string $larang) => $larang !== trim($cocok[1])));
            }
        }

        return $disallow;
    }

    /**
     * @param  list<string>  $disallow
     */
    private function periksaDisallow(array $disallow, string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH).(is_string(parse_url($url, PHP_URL_QUERY)) ? '?'.parse_url($url, PHP_URL_QUERY) : '');

        foreach ($disallow as $aturan) {
            $prefix = str_contains($aturan, '*') ? explode('*', $aturan)[0] : $aturan;

            if ($prefix === '/' || str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }

    private function muatHalaman(string $url): ?string
    {
        try {
            $respons = Http::withHeaders(['User-Agent' => config('osm.user_agent')])
                ->timeout(15)
                ->get($url);
        } catch (Throwable $e) {
            Log::warning('InfoDirektoriFetcher: '.$url.' gagal — '.$e->getMessage());

            return null;
        }

        if (! $respons->successful()) {
            Log::warning('InfoDirektoriFetcher: '.$url.' HTTP '.$respons->status().'.');

            return null;
        }

        return $respons->body();
    }

    private function muatDom(string $html): ?DOMDocument
    {
        if (! str_contains($html, '<')) {
            return null;
        }

        $doc = new DOMDocument;
        @$doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return $doc;
    }

    private function ambilDeskripsi(DOMXPath $xpath): ?string
    {
        foreach (['name="description"', 'property="og:description"', 'name="og:description"'] as $ekspresi) {
            $node = $xpath->query('//meta[@'.$ekspresi.']')->item(0);

            if ($node === null) {
                continue;
            }

            $isi = trim(preg_replace('/\s+/', ' ', (string) $node->getAttribute('content')));

            if ($isi !== '') {
                return $isi;
            }
        }

        return null;
    }

    private function ambilGambar(DOMXPath $xpath, string $url): ?string
    {
        foreach (['property="og:image"', 'name="og:image"'] as $ekspresi) {
            $node = $xpath->query('//meta[@'.$ekspresi.']')->item(0);

            if ($node !== null && trim((string) $node->getAttribute('content')) !== '') {
                return $this->absolutkan($url, trim((string) $node->getAttribute('content')));
            }
        }

        return null;
    }

    private function absolutkan(string $dasar, string $tautan): string
    {
        if (preg_match('~^https?://~i', $tautan)) {
            return $tautan;
        }

        $komponen = parse_url($dasar);
        $asli = ($komponen['scheme'] ?? 'https').'://'.($komponen['host'] ?? '');

        if (str_starts_with($tautan, '//')) {
            return ($komponen['scheme'] ?? 'https').':'.$tautan;
        }

        if (str_starts_with($tautan, '/')) {
            return $asli.$tautan;
        }

        $basis = rtrim((string) ($komponen['path'] ?? ''), '/');

        if (str_starts_with($basis, '/')) {
            $basis = substr($basis, 0, strrpos($basis, '/') ?: null);
        }

        return $asli.$basis.'/'.$tautan;
    }

    private function simpanGambar(Model $tempat, string $url): ?string
    {
        try {
            $respons = Http::withHeaders(['User-Agent' => config('osm.user_agent')])
                ->timeout(20)
                ->get($url);
        } catch (Throwable $e) {
            return null;
        }

        $tipe = $respons->header('Content-Type');

        if (! $respons->successful() || ! is_string($tipe) || ! str_starts_with($tipe, 'image/')) {
            return null;
        }

        $ukuran = strlen($respons->body());

        if ($ukuran < 1024 || $ukuran > 8 * 1024 * 1024) {
            return null;
        }

        $ekstensi = match ($tipe) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };

        $path = 'situs/'.$tempat->slug.'/cover.'.$ekstensi;

        Storage::disk('public')->put($path, $respons->body());

        return $path;
    }

    /**
     * Mencoba membaca daftar menu dari halaman-halaman menu statis.
     *
     * @return list<array{nama: string, harga: string|null}>
     */
    private function carikanMenu(string $url, string $halamanUtama): array
    {
        $kandidat = $this->tautanMenu($url, $halamanUtama);
        $host = $this->host($url);

        foreach (array_slice($kandidat, 0, 3) as $halamanMenu) {
            if ($this->host($halamanMenu) !== $host) {
                continue;
            }

            $this->jeda();

            $konten = $this->muatHalaman($halamanMenu);

            if ($konten === null) {
                continue;
            }

            $items = $this->ekstrakItems($konten);

            if (count($items) >= 3) {
                return array_slice($items, 0, 24);
            }
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function tautanMenu(string $url, string $halamanUtama): array
    {
        $dom = $this->muatDom($halamanUtama);

        if ($dom === null) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $pilihan = [];
        $host = $this->host($url);

        foreach ($xpath->query('//a[@href]') as $a) {
            $href = trim((string) $a->getAttribute('href'));
            $teks = strtolower(mb_substr(trim((string) $a->textContent), 0, 40));
            $sasaran = $this->absolutkan($url, $href);

            if ($this->host($sasaran) !== $host) {
                continue;
            }

            if (
                preg_match('~(menu|makanan|minuman|daftar)~i', $href) === 1
                || preg_match('~(menu|makanan|minuman)~i', $teks) === 1
            ) {
                $pilihan[] = $sasaran;
            }

            if (count($pilihan) >= 5) {
                break;
            }
        }

        return array_values(array_unique($pilihan));
    }

    /**
     * @return list<array{nama: string, harga: string|null}>
     */
    private function ekstrakItems(string $html): array
    {
        $dom = $this->muatDom($html);

        if ($dom === null) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $lewat = null;

        foreach (['//li', '//p'] as $ekspresi) {
            $kumpulan = [];

            foreach ($xpath->query($ekspresi) as $node) {
                $teks = trim((string) preg_replace('/\s+/', ' ', (string) $node->textContent));

                if (mb_strlen($teks) >= 3 && mb_strlen($teks) <= 80) {
                    $kumpulan[] = $teks;
                }
            }

            $kumpulan = array_values(array_unique($kumpulan));

            if (count($kumpulan) >= 3) {
                $lewat = $kumpulan;

                break;
            }
        }

        if ($lewat === null) {
            return [];
        }

        return array_map(fn (string $nama) => ['nama' => $nama, 'harga' => null], $lewat);
    }

    private function tandaiSelesai(Model $tempat): void
    {
        $tempat->info_diunduh_pada = now();
        $tempat->saveQuietly();
    }

    /**
     * @param  list<array<string, mixed>>|null  $atribusi
     * @return list<array<string, mixed>>
     */
    private function ejaAtribusi(?array $atribusi, array $baru): array
    {
        if ($atribusi === null || $atribusi === []) {
            $atribusi = [];
        }

        foreach ($atribusi as $entri) {
            if (($entri['jenis'] ?? null) === $baru['jenis'] && ($entri['sumber'] ?? null) === $baru['sumber']) {
                return $atribusi;
            }
        }

        return [...$atribusi, $baru];
    }

    private function kosong(mixed $nilai): bool
    {
        return $nilai === null
            || (is_string($nilai) && trim($nilai) === '')
            || (is_array($nilai) && $nilai === []);
    }

    private function jeda(): void
    {
        usleep((int) config('osm.jeda_ms', 150) * 1000);
    }
}
