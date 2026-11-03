<?php

namespace App\Services;

use App\Models\Kategori;
use App\Models\Kecamatan;
use Illuminate\Support\Str;

class Categorizer
{
    /**
     * Alias kecamatan yang sudah diurutkan dari yang terpanjang.
     *
     * @var array<string, string>|null
     */
    private ?array $aliasKecamatan = null;

    /**
     * Pemetaan kata kunci deklaratif ke kategori_id dan kecamatan_id
     * dengan nilai bawaan (§6.5). Peta disimpan di config/berita.php.
     *
     * Nama kecamatan ditentukan dari judul lebih dulu, baru ringkasan.
     * Isi artikel tidak dipakai karena badan berita sering menyebut
     * tempat lain sebagai pembanding.
     *
     * @return array{kategori_id: int, kecamatan_id: ?int}
     */
    public function categorize(string $judul, ?string $ringkasan = null, ?string $isi = null): array
    {
        $teks = $judul.' '.($ringkasan ?? '');

        if ($isi !== null && $isi !== '') {
            $teks .= ' '.Str::limit($isi, 800);
        }

        return [
            'kategori_id' => $this->kategoriId($teks),
            'kecamatan_id' => $this->kecamatanId($judul, $ringkasan),
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

    /**
     * Menentukan kecamatan dari judul, lalu ringkasan.
     *
     * Nama kecamatan diperiksa dari yang paling panjang supaya
     * "Ciputat Timur" tidak tertangkap "Ciputat", dan "Serpong Utara"
     * tidak tertangkap "Serpong".
     */
    private function kecamatanId(string $judul, ?string $ringkasan): ?int
    {
        foreach ([$judul, (string) $ringkasan] as $teks) {
            if (trim($teks) === '') {
                continue;
            }

            $id = $this->cocokkanWilayah(Str::lower($teks));

            if ($id !== null) {
                return $id;
            }
        }

        return null;
    }

    private function cocokkanWilayah(string $teks): ?int
    {
        foreach ($this->aliasKecamatan() as $alias => $nama) {
            if ($this->memuat($teks, $alias)) {
                return $this->idKecamatan($nama);
            }
        }

        foreach ((array) config('berita.peta_kelurahan', []) as $nama => $daftar) {
            foreach ((array) $daftar as $alias) {
                if ($this->memuat($teks, Str::lower((string) $alias))) {
                    return $this->idKecamatan((string) $nama);
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function aliasKecamatan(): array
    {
        if ($this->aliasKecamatan !== null) {
            return $this->aliasKecamatan;
        }

        $alias = [];

        foreach ((array) config('berita.peta_kecamatan', []) as $nama => $daftar) {
            foreach ((array) $daftar as $kunci) {
                $alias[Str::lower((string) $kunci)] = (string) $nama;
            }
        }

        uksort(
            $alias,
            fn (string $kiri, string $kanan): int => mb_strlen($kanan) <=> mb_strlen($kiri),
        );

        return $this->aliasKecamatan = $alias;
    }

    private function idKecamatan(string $nama): ?int
    {
        $id = Kecamatan::where('nama', $nama)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Mencari kata kunci sebagai kata utuh, bukan potongan huruf.
     */
    private function memuat(string $teks, string $kunci): bool
    {
        if ($kunci === '') {
            return false;
        }

        return preg_match('/\b'.preg_quote($kunci, '/').'\b/u', $teks) === 1;
    }
}
