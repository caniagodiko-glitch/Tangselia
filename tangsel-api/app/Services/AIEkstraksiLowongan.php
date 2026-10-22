<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ekstraksi terstruktur dari artikel lowongan kerja memakai Google Gemini.
 *
 * AI hanya memindahkan fakta yang sudah tertulis pada judul dan isi
 * artikel ke kolom terstruktur. Tidak ada fakta baru yang dikarang:
 * field yang tidak ditemukan pada sumber dikembalikan kosong.
 *
 * Mengembalikan null ketika kunci API kosong, panggilan gagal, atau
 * respons tidak dapat dibaca, sehingga pemanggil memakai
 * {@see self::parseDariJudul()} sebagai cadangan.
 */
class AIEkstraksiLowongan
{
    /**
     * Ekstrak posisi, perusahaan, deskripsi, kualifikasi, dan cara apply.
     *
     * @return array{posisi: ?string, perusahaan: ?string, deskripsi: ?string, kualifikasi: ?string, cara_apply: ?string}|null
     */
    public function ekstrak(string $judul, string $isi): ?array
    {
        $key = config('ai.key');

        if (! is_string($key) || $key === '') {
            return null;
        }

        $prompt = strtr((string) config('lowongan.prompt_ekstraksi'), [
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
                        'temperature' => 0.1,
                        'maxOutputTokens' => 900,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->skema(),
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('Gemini tidak terjangkau untuk ekstraksi lowongan: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Gemini mengembalikan status '.$response->status().' untuk ekstraksi lowongan.');

            return null;
        }

        $teks = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($teks) || trim($teks) === '') {
            return null;
        }

        $data = json_decode(trim($teks), true);

        if (! is_array($data)) {
            return null;
        }

        return [
            'posisi' => $this->bersihkan($data['posisi'] ?? null),
            'perusahaan' => $this->bersihkan($data['perusahaan'] ?? null),
            'deskripsi' => $this->bersihkan($data['deskripsi'] ?? null),
            'kualifikasi' => $this->bersihkan($data['kualifikasi'] ?? null),
            'cara_apply' => $this->bersihkan($data['cara_apply'] ?? null),
        ];
    }

    /**
     * Membersihkan judul dari sufiks sumber dan ekor lokasi.
     *
     * Judul hasil agregator memuat nama sumber di bagian akhir, misalnya
     * "Staff Admin di PT Contoh - LinkedIn". Sufiks itu bukan bagian dari
     * lowongan dan tidak boleh ikut disimpan maupun ditampilkan.
     */
    public function rapikanJudul(string $judul, ?string $sumberNama = null): string
    {
        $teks = trim((string) preg_replace('/\s+/', ' ', $judul));

        if ($sumberNama !== null && $sumberNama !== '') {
            $pola = '\s*[-–—|]\s*'.preg_quote($sumberNama, '/').'\s*$';
            $teks = $this->buangAkhir($teks, $pola);
        }

        $teks = $this->buangAkhir($teks, '\s*[-–—|]\s*(LinkedIn|Glints|Karir\.com|Jobstreet|Indeed)\s*$');
        $teks = $this->buangAkhir($teks, '\s+Terbaru\s+Hari\s+Ini.*$');
        $teks = $this->buangAkhir($teks, '\s+provided\s+pay\s+range\s*$');

        return trim($teks);
    }

    /**
     * Cadangan tanpa AI: membaca posisi dan perusahaan dari pola judul.
     *
     * Hanya pola yang lazim pada job board yang dikenali. Bila tidak ada
     * pola yang cocok, judul dibiarkan apa adanya sebagai posisi.
     *
     * @return array{posisi: ?string, perusahaan: ?string}
     */
    public function parseDariJudul(string $judul): array
    {
        $teks = $this->rapikanJudul($judul);

        // Ekor lokasi pada pola job board. Pengulangan diperlukan karena
        // judul memuat lokasi dua kali, misalnya "FIT HUB membuka lowongan
        // Personal Trainer - Tangerang Selatan di Tangerang Selatan".
        for ($putaran = 0; $putaran < 3; $putaran++) {
            $sebelum = $teks;

            $teks = $this->buangAkhir($teks, '\s*[—–-]\s*(Kota\s+)?Tangerang\s+Selatan\s*$');
            $teks = $this->buangAkhir($teks, '\s+di\s+(Kota\s+)?Tangerang\s+Selatan(?:\s*,\s*Banten\s*,\s*Indonesia)?\s*$');

            if ($teks === $sebelum) {
                break;
            }
        }

        // "Perusahaan hiring Posisi in Lokasi".
        if (preg_match('/^(?<perusahaan>.+?)\s+hiring\s+(?<posisi>.+?)(?:\s+in\s+.+)?$/i', $teks, $m)) {
            return $this->hasil($m['perusahaan'], $m['posisi']);
        }

        // "Perusahaan membuka lowongan Posisi".
        if (preg_match('/^(?<perusahaan>.+?)\s+(?:membuka|buka|opens?)\s+lowongan\s+(?<posisi>.+)$/i', $teks, $m)) {
            return $this->hasil($m['perusahaan'], $m['posisi']);
        }

        // "Posisi by Perusahaan".
        if (preg_match('/^(?<posisi>.+?)\s+by\s+(?<perusahaan>.+)$/i', $teks, $m)) {
            return $this->hasil($m['perusahaan'], $m['posisi']);
        }

        // "Loker Perusahaan" pada job board. Diperiksa sebelum aturan "di"
        // agar nama daerah tidak tertukar dengan nama perusahaan.
        if (preg_match('/^Loker\s+(?<perusahaan>.+?)(?:\s+di\s+.+)?$/iu', $teks, $m)) {
            return $this->hasil($m['perusahaan'], null);
        }

        // "Posisi di Perusahaan". Ekor klausa seperti ", gaji Rp 4 juta"
        // diperbolehkan karena sering muncul pada judul berita lowongan.
        if (preg_match('/^(?<posisi>[^,]{3,80}?)\s+di\s+(?<perusahaan>[^,]{2,80}?)(?:\s*,.*)?$/i', $teks, $m)) {
            return $this->hasil($m['perusahaan'], $m['posisi']);
        }

        // "Lowongan Kerja Perusahaan" pada job board resmi. Sisa judul
        // yang menyebut bulan, tahun, atau kata "posisi" bukan nama
        // perusahaan melainkan keterangan lowongan.
        if (preg_match('/^Lowongan\s+Kerja\s+(?<sisa>.+)$/i', $teks, $m)) {
            $sisa = trim($m['sisa']);

            if ($this->bukanNamaPerusahaan($sisa)) {
                return ['perusahaan' => null, 'posisi' => $this->rapikanPosisi($sisa)];
            }

            return $this->hasil($sisa, null);
        }

        // "Posisi @ Perusahaan". Pola ini lazim pada judul berita lowongan
        // yang memakai tanda baca sebagai pemisah.
        if (preg_match('/^(?<posisi>[^@]{3,80}?)\s+@\s+(?<perusahaan>.+)$/u', $teks, $m)) {
            return $this->hasil($m['perusahaan'], $m['posisi']);
        }

        // "Perusahaan - Posisi" pada job board.
        if (preg_match('/^(?<perusahaan>[^-]{2,80}?)\s+-\s+(?<posisi>[^-]{3,80})$/u', $teks, $m)) {
            return $this->hasil($m['perusahaan'], $m['posisi']);
        }

        return ['perusahaan' => null, 'posisi' => $this->bersihkan($teks)];
    }

    /**
     * Skema respons Gemini agar keluaran selalu berupa JSON valid.
     *
     * @return array<string, mixed>
     */
    private function skema(): array
    {
        $teks = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'posisi' => $teks,
                'perusahaan' => $teks,
                'deskripsi' => $teks,
                'kualifikasi' => $teks,
                'cara_apply' => $teks,
            ],
            'required' => ['posisi', 'perusahaan', 'deskripsi', 'kualifikasi', 'cara_apply'],
        ];
    }

    /**
     * @return array{posisi: ?string, perusahaan: ?string}
     */
    private function hasil(?string $perusahaan, ?string $posisi): array
    {
        $perusahaan = $this->bersihkan($perusahaan);
        $posisi = $this->bersihkan($posisi);

        if ($posisi !== null) {
            $posisi = $this->rapikanPosisi($posisi);
        }

        return [
            'perusahaan' => $perusahaan,
            'posisi' => $posisi,
        ];
    }

    /**
     * Nama bulan dalam bahasa Indonesia, dipakai untuk membuang
     * keterangan bulan dan tahun dari nama posisi.
     *
     * @var list<string>
     */
    private const BULAN = [
        'januari', 'februari', 'maret', 'april', 'mei', 'juni',
        'juli', 'agustus', 'september', 'oktober', 'november', 'desember',
    ];

    /**
     * Membersihkan keterangan berlebihan di depan nama posisi.
     *
     * Judul berita lowongan sering diawali "Lowongan Kerja", bulan dan
     * tahun, atau kata "Posisi" yang tidak termasuk nama posisi itu
     * sendiri.
     */
    private function rapikanPosisi(string $posisi): string
    {
        $teks = trim($posisi);
        $bulan = implode('|', self::BULAN);

        $teks = (string) preg_replace('/^(?:info\s+)?lowongan\s+kerja\s+/iu', '', $teks);
        $teks = (string) preg_replace('/^lowongan\s+/iu', '', $teks);
        $teks = (string) preg_replace('/^loker\s+/iu', '', $teks);
        $teks = (string) preg_replace('/^(?:'.$bulan.')\s+\d{4}\s+/iu', '', $teks);
        $teks = (string) preg_replace('/^posisi\s+/iu', '', $teks);

        return trim($teks);
    }

    /**
     * Memeriksa apakah sisa judul bukan nama perusahaan.
     *
     * Sisa yang diawali bulan atau memuat kata "posisi" berisi
     * keterangan lowongan, bukan nama perusahaan.
     */
    private function bukanNamaPerusahaan(string $teks): bool
    {
        $bulan = implode('|', self::BULAN);

        if (preg_match('/^(?:'.$bulan.')\b/iu', $teks) === 1) {
            return true;
        }

        return preg_match('/\bposisi\b/iu', $teks) === 1;
    }

    /**
     * Menghapus pola di ujung teks bila cocok.
     */
    private function buangAkhir(string $teks, string $pola): string
    {
        $hasil = preg_replace('/'.$pola.'/iu', '', $teks, 1);

        return trim((string) $hasil);
    }

    private function bersihkan(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $hasil = trim((string) preg_replace('/\s+/', ' ', $nilai));

        return $hasil === '' ? null : $hasil;
    }
}
