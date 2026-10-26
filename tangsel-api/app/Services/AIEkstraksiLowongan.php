<?php

namespace App\Services;

use Illuminate\Support\Str;

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
        $prompt = strtr((string) config('lowongan.prompt_ekstraksi'), [
            ':judul' => mb_substr($judul, 0, 300),
            ':isi' => mb_substr($isi, 0, 6000),
        ]);

        $teks = app(GeminiClient::class)->teks($prompt, [
            'temperature' => 0.1,
            'maxOutputTokens' => 900,
            'responseMimeType' => 'application/json',
            'responseSchema' => $this->skema(),
        ], 'ekstraksi lowongan');

        if ($teks === null) {
            return null;
        }

        $data = json_decode($teks, true);

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

        // "Loker Posisi di Kota" pada job board. Isi di antara "Loker" dan
        // nama kota adalah nama posisi yang dipakai job board, misalnya
        // "Loker Spg Peralatan Rumah Tangga di Tangerang Selatan".
        // Diperiksa sebelum aturan "di" agar nama daerah tidak tertukar
        // dengan nama posisi.
        if (preg_match('/^Loker\s+(?<posisi>.+?)(?:\s+di\s+.+)?$/iu', $teks, $m)) {
            return $this->hasil(null, $m['posisi']);
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

        // Judul yang isinya hanya nama perusahaan, misalnya
        // "PT Home Credit Indonesia". Nama itu bukan nama posisi.
        if (preg_match('/^(?<perusahaan>(?:PT\.?|CV|UD|Badan|BPR|Tbk)\.?\s+\S.{1,80})$/u', $teks, $m)) {
            return $this->hasil($m['perusahaan'], null);
        }

        return ['perusahaan' => null, 'posisi' => $this->bersihkan($teks)];
    }

    /**
     * Membuang isi posisi dan perusahaan yang hanya berupa kalimat judul.
     *
     * Pembacaan cadangan berbasis pola judul bekerja paling baik pada
     * judul job board yang terstruktur. Pada judul berita, pola yang sama
     * dapat menghasilkan potongan kalimat seperti "Kerja untuk Diverse
     * Lulusan" atau "Oktober 2026". Metode ini membersihkan hasil
     * pembacaan cadangan agar kolom posisi dan perusahaan hanya berisi
     * nama yang benar-benar informatif.
     *
     * @param  array{posisi: ?string, perusahaan: ?string, deskripsi: ?string, kualifikasi: ?string, cara_apply: ?string}  $hasil
     * @return array{posisi: ?string, perusahaan: ?string, deskripsi: ?string, kualifikasi: ?string, cara_apply: ?string}
     */
    public function rapikanHasil(array $hasil): array
    {
        $hasil['posisi'] = $this->wajarSebagaiPosisi($hasil['posisi'] ?? null);
        $hasil['perusahaan'] = $this->wajarSebagaiPerusahaan($hasil['perusahaan'] ?? null);

        return $hasil;
    }

    /**
     * Memeriksa apakah nilai layak dipakai sebagai nama posisi.
     *
     * Nama posisi bukan nama perusahaan, bukan keterangan bulan, dan
     * bukan potongan kalimat judul berita.
     */
    private function wajarSebagaiPosisi(?string $posisi): ?string
    {
        $teks = trim((string) $posisi);

        if ($teks === '' || mb_strlen($teks) > 80) {
            return null;
        }

        $huruf = Str::lower($teks);

        if (preg_match('/^(?:pt|cv|ud|badan|bpr|tbk)\b/u', $huruf) === 1) {
            return null;
        }

        if (preg_match('/^(?:'.$this->bulanRegex().')\b/u', $huruf) === 1) {
            return null;
        }

        if (preg_match('/^(?:satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|sepuluh|sejumlah|ribuan|ribu)\b/u', $huruf) === 1) {
            return null;
        }

        if ($this->memuatKataKeterangan($huruf)) {
            return null;
        }

        return $teks;
    }

    /**
     * Memeriksa apakah nilai layak dipakai sebagai nama perusahaan.
     */
    private function wajarSebagaiPerusahaan(?string $perusahaan): ?string
    {
        $teks = trim((string) $perusahaan);

        if ($teks === '' || mb_strlen($teks) > 100) {
            return null;
        }

        $huruf = Str::lower($teks);

        if (preg_match('/^(?:kota|tangerang|kecamatan|kelurahan|jalan|jl)\b/u', $huruf) === 1) {
            return null;
        }

        if ($this->memuatKataKeterangan($huruf)) {
            return null;
        }

        return $teks;
    }

    /**
     * Apakah teks memuat kata yang menandai kalimat judul berita.
     */
    private function memuatKataKeterangan(string $huruf): bool
    {
        foreach ((array) config('lowongan.kata_keterangan', []) as $kata) {
            $kata = (string) $kata;

            if ($kata === '') {
                continue;
            }

            if (preg_match('/\b'.preg_quote($kata, '/').'\b/u', $huruf) === 1) {
                return true;
            }
        }

        return false;
    }

    private function bulanRegex(): string
    {
        return implode('|', self::BULAN);
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
        $bulan = $this->bulanRegex();

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
        $bulan = $this->bulanRegex();

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
