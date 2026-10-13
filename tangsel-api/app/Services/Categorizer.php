<?php

namespace App\Services;

use App\Models\Kategori;
use App\Models\Kecamatan;
use Illuminate\Support\Str;

class Categorizer
{
    /**
     * Pemetaan kata kunci deklaratif ke kategori_id dan kecamatan_id
     * dengan nilai bawaan (§6.5). Peta disimpan di config/berita.php.
     *
     * @return array{kategori_id: int, kecamatan_id: ?int}
     */
    public function categorize(string $teks): array
    {
        return [
            'kategori_id' => $this->kategoriId($teks),
            'kecamatan_id' => $this->kecamatanId($teks),
        ];
    }

    private function kategoriId(string $teks): int
    {
        $teks = Str::lower($teks);
        $terbaik = null;
        $skorTerbaik = 0;

        foreach (config('berita.peta_kategori', []) as $nama => $kataKunci) {
            $skor = 0;

            foreach ($kataKunci as $kunci) {
                if (Str::contains($teks, Str::lower($kunci))) {
                    $skor++;
                }
            }

            if ($skor > $skorTerbaik) {
                $skorTerbaik = $skor;
                $terbaik = $nama;
            }
        }

        $id = $terbaik === null
            ? Kategori::where('slug', config('berita.kategori_bawaan'))->value('id')
            : Kategori::where('nama', $terbaik)->where('scope', 'berita')->value('id');

        if ($id === null) {
            $id = Kategori::where('scope', 'berita')->orderBy('urutan')->value('id');
        }

        return (int) $id;
    }

    private function kecamatanId(string $teks): ?int
    {
        $teks = Str::lower($teks);

        foreach (config('berita.peta_kecamatan', []) as $nama => $kataKunci) {
            foreach ($kataKunci as $kunci) {
                if (Str::contains($teks, Str::lower($kunci))) {
                    $id = Kecamatan::where('nama', $nama)->value('id');

                    return $id === null ? null : (int) $id;
                }
            }
        }

        return null;
    }
}
