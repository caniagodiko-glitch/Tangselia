<?php

namespace App\Services;

use App\Models\Kategori;
use App\Models\Kecamatan;
use Illuminate\Support\Str;

/**
 * Penentuan kategori bidang, kecamatan, dan jenis lowongan kerja.
 *
 * Semua pemetaan bersifat deterministik dan memakai peta kata kunci pada
 * config/lowongan.php, sehingga hasil klasifikasi dapat diuji dan
 * ditelusuri tanpa bergantung pada model bahasa.
 */
class LowonganClassifier
{
    /**
     * Klasifikasi satu lowongan.
     *
     * @return array{kategori_id: ?int, kecamatan_id: ?int, jenis: string}
     */
    public function classify(string $teks): array
    {
        return [
            'kategori_id' => $this->kategoriId($teks),
            'kecamatan_id' => $this->kecamatanId($teks),
            'jenis' => $this->jenis($teks),
        ];
    }

    /**
     * Kategori bidang pekerjaan berdasarkan kata kunci.
     */
    private function kategoriId(string $teks): ?int
    {
        $huruf = Str::lower($teks);
        $terbaik = null;
        $terbaikSkor = 0;

        foreach ((array) config('lowongan.peta_kategori', []) as $nama => $kataKunci) {
            $skor = 0;

            foreach ($kataKunci as $kata) {
                if (str_contains($huruf, (string) $kata)) {
                    $skor++;
                }
            }

            if ($skor > $terbaikSkor) {
                $terbaikSkor = $skor;
                $terbaik = $nama;
            }
        }

        if ($terbaik !== null) {
            $kategori = Kategori::where('scope', 'lowongan')
                ->where('nama', $terbaik)
                ->first();

            if ($kategori !== null) {
                return $kategori->id;
            }
        }

        $cadangan = Kategori::where('scope', 'lowongan')
            ->where('slug', config('lowongan.kategori_bawaan'))
            ->first();

        return $cadangan?->id;
    }

    /**
     * Kecamatan berdasarkan nama yang disebut pada teks lowongan.
     */
    private function kecamatanId(string $teks): ?int
    {
        $huruf = Str::lower($teks);

        foreach (Kecamatan::orderBy('urutan')->get() as $kecamatan) {
            $nama = Str::lower($kecamatan->nama);

            if (str_contains($huruf, $nama)) {
                return $kecamatan->id;
            }
        }

        return null;
    }

    /**
     * Jenis lowongan: magang, part-time, atau karier penuh.
     */
    private function jenis(string $teks): string
    {
        $huruf = Str::lower($teks);

        foreach ((array) config('lowongan.peta_jenis', []) as $jenis => $kataKunci) {
            foreach ($kataKunci as $kata) {
                if (str_contains($huruf, (string) $kata)) {
                    return (string) $jenis;
                }
            }
        }

        return 'karir';
    }
}
