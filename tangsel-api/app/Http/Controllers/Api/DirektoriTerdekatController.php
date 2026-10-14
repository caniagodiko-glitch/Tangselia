<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DirektoriRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class DirektoriTerdekatController extends Controller
{
    /**
     * Jumlah maksimum tempat terdekat yang dikembalikan.
     */
    private const BATAS = 6;

    /**
     * Radius pencarian kandidat (derajat, ±6 km) untuk menyaring sebelum
     * menghitung jarak sesungguhnya.
     */
    private const DELTA = 0.06;

    /**
     * Daftar tempat aktif terdekat dari satu direktori, dihitung memakai
     * rumus haversine agar portabel di MySQL maupun SQLite.
     */
    public function index(string $fitur, string $slug): JsonResponse
    {
        $modelClass = DirektoriRegistry::model($fitur);

        $pusat = $modelClass::with('kecamatan:id,nama,slug')
            ->where('slug', $slug)
            ->where('status', 'aktif')
            ->firstOrFail();

        $kandidat = $modelClass::with('kecamatan:id,nama,slug')
            ->where('status', 'aktif')
            ->whereKeyNot($pusat->getKey())
            ->whereBetween('latitude', [$pusat->latitude - self::DELTA, $pusat->latitude + self::DELTA])
            ->whereBetween('longitude', [$pusat->longitude - self::DELTA, $pusat->longitude + self::DELTA])
            ->limit(60)
            ->get();

        $hasil = $this->urutkan($kandidat, $pusat)
            ->take(self::BATAS)
            ->values();

        return response()->json(['data' => $hasil]);
    }

    /**
     * Hitung jarak tiap kandidat lalu urutkan dari yang paling dekat.
     *
     * @param  Collection<int, Model>  $kandidat
     * @return Collection<int, array<string, mixed>>
     */
    private function urutkan(Collection $kandidat, Model $pusat): Collection
    {
        return $kandidat
            ->map(fn (Model $item): array => [
                'id' => $item->getKey(),
                'nama' => $item->nama,
                'slug' => $item->slug,
                'jenis' => $item->jenis,
                'alamat' => $item->alamat,
                'kecamatan' => $item->kecamatan?->nama,
                'rating' => $item->rating ?? null,
                'gambar' => $item->gambar,
                'jarak_m' => (int) round($this->jarak(
                    (float) $pusat->latitude,
                    (float) $pusat->longitude,
                    (float) $item->latitude,
                    (float) $item->longitude,
                )),
            ])
            ->sortBy('jarak_m');
    }

    /**
     * Jarak lingkaran besar antara dua koordinat, dalam meter.
     */
    private function jarak(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $bumi = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $bumi * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
