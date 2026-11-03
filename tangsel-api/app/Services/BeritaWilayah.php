<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Penentu relevansi wilayah berita agregat.
 *
 * Kueri berita Tangerang Selatan juga mengembalikan berita Kota Tangerang,
 * Kabupaten Tangerang, dan berita nasional karena nama daerahnya mirip.
 * Pemeriksa ini memakai penanda wilayah yang eksplisit supaya hanya berita
 * yang benar-benar berada di Tangerang Selatan yang tayang.
 *
 * Sumber umum yang tidak menyebut wilayah sama sekali tidak langsung
 * dibuang: berita itu disimpan sebagai draft agar administrator yang
 * memutuskan. Hanya sumber nasional yang ditolak tanpa penanda wilayah.
 */
class BeritaWilayah
{
    public const TERIMA = 'terima';

    public const DRAFT = 'draft';

    public const TOLAK = 'tolak';

    /**
     * Apakah penyaringan wilayah diaktifkan.
     */
    public function aktif(): bool
    {
        return (bool) config('berita.filter_wilayah.aktif', true);
    }

    /**
     * Menentukan keputusan penyaringan untuk satu item berita.
     *
     * Bukti wilayah dicari pada judul dan ringkasan, sedangkan penanda
     * wilayah lain hanya diperiksa pada judul. Badan berita sering
     * menyebut Jakarta sebagai pembuka atau pembanding sehingga pemeriksaan
     * pada seluruh teks akan menolak berita Tangsel yang sah.
     */
    public function keputusan(string $judul, ?string $ringkasan, ?string $sumber): string
    {
        $judul = $this->tanpaAkhiranSumber($judul, $sumber);

        $adaBukti = $this->menyebutTangsel($judul.' '.($ringkasan ?? ''));
        $adaLuar = $this->menunjukWilayahLain($judul);

        if ($this->sumberLokal($sumber)) {
            return $adaLuar ? self::DRAFT : self::TERIMA;
        }

        if ($this->sumberNasional($sumber) && ! $adaBukti) {
            return self::TOLAK;
        }

        if (! $adaBukti) {
            return $adaLuar ? self::TOLAK : self::DRAFT;
        }

        return $adaLuar ? self::DRAFT : self::TERIMA;
    }

    /**
     * Apakah teks menyebut wilayah Tangerang Selatan.
     */
    public function menyebutTangsel(string $teks): bool
    {
        $huruf = Str::lower($teks);

        foreach ((array) config('berita.penanda_wilayah', []) as $penanda) {
            if ($this->memuat($huruf, (string) $penanda)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah teks secara eksplisit menunjuk wilayah selain Tangsel.
     *
     * "Kota Tangerang Selatan" tetap dianggap Tangsel, sehingga frasa itu
     * tidak dihitung sebagai wilayah lain.
     */
    public function menunjukWilayahLain(string $teks): bool
    {
        $huruf = Str::lower($teks);

        // \"Tangerang\" tanpa \"Selatan\" hampir selalu Kota/Kabupaten
        // Tangerang. \"Tangerang Selatan\" dan \"Tangsel\" tidak kena aturan
        // ini karena kata berikutnya adalah \"selatan\".
        if (preg_match('/\btangerang\b(?!\s+selatan)/u', $huruf) === 1) {
            return true;
        }

        foreach ((array) config('berita.penanda_wilayah_lain', []) as $penanda) {
            $penanda = (string) $penanda;

            if ($penanda === '') {
                continue;
            }

            if (str_contains($penanda, 'tangerang')) {
                $pola = '/\b'.preg_quote($penanda, '/').'\b(?!\s+selatan)/u';

                if (preg_match($pola, $huruf) === 1) {
                    return true;
                }

                continue;
            }

            if ($this->memuat($huruf, $penanda)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah sumber berita memakai kueri yang sudah dibatasi Tangsel.
     */
    public function sumberLokal(?string $sumber): bool
    {
        return $this->adaDalamDaftar($sumber, 'sumber_lokal');
    }

    /**
     * Apakah sumber berita berskala nasional.
     */
    public function sumberNasional(?string $sumber): bool
    {
        return $this->adaDalamDaftar($sumber, 'sumber_nasional');
    }

    /**
     * Apakah tanggal terbit melewati batas umur berita.
     */
    public function terlaluTua(?CarbonInterface $terbit, ?CarbonInterface $sekarang = null): bool
    {
        $hari = config('berita.filter_wilayah.maks_umur_hari');

        if ($terbit === null || ! is_numeric($hari) || (int) $hari <= 0) {
            return false;
        }

        return $terbit->lt(($sekarang ?? now())->copy()->subDays((int) $hari));
    }

    /**
     * Membuang akhiran nama penerbit pada judul.
     *
     * Umpan RSS sering menambahkan " - Nama Penerbit" pada judul. Akhiran
     * yang memuat nama daerah, misalnya "Tangerang Online", tidak boleh
     * dianggap sebagai penanda wilayah berita.
     */
    private function tanpaAkhiranSumber(string $judul, ?string $sumber): string
    {
        if ($sumber === null || trim($sumber) === '') {
            return $judul;
        }

        $pola = '/\s+[-–—|]\s+'.preg_quote(trim($sumber), '/').'\s*$/iu';
        $hasil = preg_replace($pola, '', $judul);

        return $hasil === null ? $judul : trim($hasil);
    }

    /**
     * @param  array<int, mixed>  $daftar
     */
    private function adaDalamDaftar(?string $sumber, string $kunci): bool
    {
        if ($sumber === null || trim($sumber) === '') {
            return false;
        }

        $daftar = array_map(
            fn ($nilai): string => Str::lower(trim((string) $nilai)),
            (array) config('berita.filter_wilayah.'.$kunci, []),
        );

        return in_array(Str::lower(trim($sumber)), $daftar, true);
    }

    /**
     * Mencari penanda sebagai kata utuh, bukan sebagai potongan kata.
     */
    private function memuat(string $huruf, string $penanda): bool
    {
        if ($penanda === '') {
            return false;
        }

        return preg_match('/\b'.preg_quote($penanda, '/').'\b/u', $huruf) === 1;
    }
}
