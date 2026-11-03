<?php

namespace App\Services;

use App\Models\Berita;
use App\Models\Sumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NewsFetcher
{
    /**
     * Nama agen pengidentifikasi, konsisten dengan ArticleContentFetcher.
     */
    private const USER_AGENT = 'TANGSEL-LIA-Bot/1.0 (+https://tangselia.id)';

    /**
     * Batas item yang diproses per sumber dalam satu kali jalan,
     * agar beban dan lamanya proses tetap terkendali.
     */
    private const MAX_ITEMS_PER_SOURCE = 20;

    public function __construct(
        private FeedParser $feedParser,
        private ArticleContentFetcher $contentFetcher,
        private AISummarizer $summarizer,
        private Categorizer $categorizer,
        private BeritaWilayah $wilayah,
    ) {}

    /**
     * Mengambil seluruh sumber aktif (atau satu sumber), menyimpan item
     * baru sebagai berita agregat, lalu memperbarui statistik sumber.
     *
     * @return array{sumber: int, dibuat: int, draft: int, dilewati: int, ditolak: int, gagal: int}
     */
    public function run(?string $source = null): array
    {
        $stats = [
            'sumber' => 0,
            'dibuat' => 0,
            'draft' => 0,
            'dilewati' => 0,
            'ditolak' => 0,
            'gagal' => 0,
        ];

        $query = Sumber::query()->where('aktif', true)->where('tipe', 'berita');

        if ($source !== null && $source !== '') {
            $query->where(function ($inner) use ($source) {
                $inner->where('id', $source)
                    ->orWhere('nama', 'like', '%'.$source.'%');
            });
        }

        foreach ($query->get() as $sumber) {
            $stats['sumber']++;

            try {
                $hasil = $this->fetchFromSource($sumber);
                $stats['dibuat'] += $hasil['dibuat'];
                $stats['draft'] += $hasil['draft'];
                $stats['dilewati'] += $hasil['dilewati'];
                $stats['ditolak'] += $hasil['ditolak'];

                $sumber->update([
                    'last_fetched_at' => now(),
                    'last_status' => 'ok',
                    'last_error' => null,
                    'item_count' => $hasil['item_count'],
                ]);
            } catch (Throwable $e) {
                $stats['gagal']++;
                Log::warning('berita:fetch gagal pada sumber "'.$sumber->nama.'": '.$e->getMessage());

                $sumber->update([
                    'last_fetched_at' => now(),
                    'last_status' => 'error',
                    'last_error' => Str::limit($e->getMessage(), 400),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Proses satu sumber: unduh feed, dedup, saring wilayah, lalu simpan.
     *
     * @return array{dibuat: int, draft: int, dilewati: int, ditolak: int, item_count: int}
     */
    private function fetchFromSource(Sumber $sumber): array
    {
        $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout(15)
            ->get($sumber->feed_url);

        if (! $response->successful()) {
            throw new RuntimeException('HTTP '.$response->status().' untuk '.$sumber->feed_url);
        }

        $items = $this->feedParser->parse($response->body());
        $dibuat = 0;
        $draft = 0;
        $dilewati = 0;
        $ditolak = 0;

        foreach (array_slice($items, 0, self::MAX_ITEMS_PER_SOURCE) as $item) {
            $urlAsli = $item['link'];

            if ($item['judul'] === '' || $urlAsli === null || $urlAsli === '') {
                $dilewati++;

                continue;
            }

            $urlAsli = mb_substr($urlAsli, 0, 500);

            if (Berita::where('url_asli', $urlAsli)->exists()) {
                $dilewati++;

                continue;
            }

            if ($this->wilayah->terlaluTua($item['pub_date'])) {
                $ditolak++;

                continue;
            }

            $keputusan = $this->wilayah->aktif()
                ? $this->wilayah->keputusan($item['judul'], $item['ringkasan'] ?: null, $sumber->nama)
                : BeritaWilayah::TERIMA;

            if ($keputusan === BeritaWilayah::TOLAK) {
                $ditolak++;

                continue;
            }

            if ($this->create($sumber, $item, $urlAsli, $keputusan) === null) {
                $dilewati++;
            } elseif ($keputusan === BeritaWilayah::DRAFT) {
                $draft++;
            } else {
                $dibuat++;
            }
        }

        return [
            'dibuat' => $dibuat,
            'draft' => $draft,
            'dilewati' => $dilewati,
            'ditolak' => $ditolak,
            'item_count' => count($items),
        ];
    }

    /**
     * @param  array{judul: string, link: ?string, pub_date: ?Carbon, ringkasan: string, gambar: ?string, source_name: ?string, source_url: ?string}  $item
     */
    private function create(Sumber $sumber, array $item, string $urlAsli, string $keputusan): ?Berita
    {
        $ringkasan = $item['ringkasan'] !== '' ? $item['ringkasan'] : null;
        $diringkasOleh = 'feed';
        $isiArtikel = null;

        if ($this->summarizerHasApiKey()) {
            $isiArtikel = $this->contentFetcher->fetch($urlAsli);

            if ($isiArtikel !== null) {
                $ringkasanAi = $this->summarizer->summarize($item['judul'], $isiArtikel);

                if ($ringkasanAi !== null) {
                    $ringkasan = $ringkasanAi;
                    $diringkasOleh = 'ai';
                }
            }
        }

        $kategori = $this->categorizer->categorize($item['judul'], $ringkasan, $isiArtikel);

        try {
            return Berita::create([
                'kategori_id' => $kategori['kategori_id'],
                'kecamatan_id' => $kategori['kecamatan_id'],
                'editor_id' => null,
                'tipe' => 'agregat',
                'judul' => $item['judul'],
                'slug' => $this->uniqueSlug($item['judul'], $urlAsli),
                'ringkasan' => $ringkasan,
                'diringkas_oleh' => $diringkasOleh,
                'konten' => null,
                'url_asli' => $urlAsli,
                'sumber' => $item['source_name'] ?? $sumber->nama,
                'sumber_url' => $item['source_url'] ?? $this->origin($sumber->feed_url),
                'gambar' => $item['gambar'],
                'status' => $keputusan === BeritaWilayah::DRAFT ? 'draft' : 'published',
                'published_at' => $item['pub_date'] ?? now(),
                'fetched_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('berita:fetch gagal menyimpan "'.$item['judul'].'": '.$e->getMessage());

            return null;
        }
    }

    private function summarizerHasApiKey(): bool
    {
        $key = config('ai.key');

        return is_string($key) && $key !== '';
    }

    private function uniqueSlug(string $judul, string $urlAsli): string
    {
        $slug = Str::slug($judul);

        if ($slug === '') {
            $slug = 'berita-'.substr(md5($urlAsli), 0, 8);
        }

        $basis = $slug;
        $suffix = substr(md5($urlAsli), 0, 6);
        $counter = 1;

        while (Berita::where('slug', $slug)->exists()) {
            $slug = $basis.'-'.$suffix;

            if ($counter > 1) {
                $slug .= '-'.$counter;
            }

            $counter++;
        }

        return $slug;
    }

    private function origin(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'];
    }
}
