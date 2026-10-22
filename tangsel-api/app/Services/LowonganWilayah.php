<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Penentu wilayah lowongan kerja.
 *
 * Kueri pencarian lowongan di Tangerang Selatan juga mengembalikan berita
 * lowongan dari Kota Tangerang dan kota lain karena kedua wilayah itu
 * berbagi nama daerah. Pemeriksa ini memakai penanda wilayah yang
 * eksplisit supaya lowongan yang ditampilkan benar-benar berada di
 * Tangerang Selatan.
 */
class LowonganWilayah
{
    /**
     * Apakah teks menyebut wilayah Tangerang Selatan.
     */
    public function menyebutTangsel(string $teks): bool
    {
        $huruf = Str::lower($teks);

        foreach ((array) config('lowongan.penanda_wilayah', []) as $penanda) {
            if ($this->memuat($huruf, (string) $penanda)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah teks secara eksplisit menunjuk wilayah selain Tangsel.
     *
     * "Kota Tangerang Selatan" tetap regarded sebagai Tangsel, sehingga
     * frasa tersebut tidak dihitung sebagai wilayah lain.
     */
    public function menunjukWilayahLain(string $teks): bool
    {
        $huruf = Str::lower($teks);

        foreach ((array) config('lowongan.penanda_wilayah_lain', []) as $penanda) {
            $penanda = (string) $penanda;

            if ($penanda === 'kota tangerang' && preg_match('/\bkota\s+tangerang\b(?!\s+selatan)/u', $huruf) === 1) {
                return true;
            }

            if ($penanda !== 'kota tangerang' && $this->memuat($huruf, $penanda)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah lowongan layak ditampilkan sebagai lowongan di Tangsel.
     *
     * Sumber job board memakai kueri yang sudah dibatasi pada wilayah
     * Tangsel sehingga isinya tidak wajib menyebut wilayah secara
     * eksplisit. Sumber umum wajib menyebut wilayah Tangsel supaya
     * berita lowongan dari Kota Tangerang tidak ikut masuk. Penyebutan
     * wilayah lain diabaikan bila teks sudah menyebut Tangsel, karena
     * banyak lowongan di Tangsel juga menyebut Jabodetabek.
     *
     * @param  bool  $wajibSebutWilayah  apakah wilayah harus disebut eksplisit
     */
    public function layak(string $teks, bool $wajibSebutWilayah = true): bool
    {
        if ($this->menyebutTangsel($teks)) {
            return true;
        }

        if ($wajibSebutWilayah) {
            return false;
        }

        return ! $this->menunjukWilayahLain($teks);
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
