<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FasilitasOlahraga;
use App\Models\LayananKesehatan;
use App\Models\Tempat;
use App\Services\GooglePlacesLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DirektoriGoogleController extends Controller
{
    /**
     * Peta fitur direktori ke model Eloquent.
     *
     * @var array<string, class-string<Model>>
     */
    private const MODEL = [
        'kuliner' => Tempat::class,
        'olahraga' => FasilitasOlahraga::class,
        'kesehatan' => LayananKesehatan::class,
    ];

    /**
     * Detail live dari Google Places: rating, ulasan, jam, dan foto.
     * Mengembalikan data null bila API key kosong atau tidak ada kecocokan,
     * sehingga frontend tetap menampilkan data inti dari OSM.
     */
    public function detail(string $fitur, string $slug, GooglePlacesLookup $lookup): JsonResponse
    {
        $tempat = $this->temukan($fitur, $slug);

        $placeId = $tempat->google_place_id;

        if ($placeId === null) {
            $placeId = $lookup->cari($tempat->nama, (float) $tempat->latitude, (float) $tempat->longitude);
        }

        if ($placeId === null) {
            return response()->json(['data' => null, 'sumber' => 'OpenStreetMap']);
        }

        $detail = $lookup->detail($placeId);

        if ($detail === null) {
            return response()->json(['data' => null, 'sumber' => 'OpenStreetMap']);
        }

        return response()->json([
            'data' => $detail,
            'sumber' => 'Google',
            'atribusi' => 'Data rating dan ulasan bersumber dari Google.',
        ]);
    }

    /**
     * Proxy foto Google agar API key tidak terekspos ke peramban.
     */
    public function foto(Request $request, string $fitur, string $slug, GooglePlacesLookup $lookup): Response
    {
        $validated = $request->validate([
            'photo' => ['required', 'string', 'max:500'],
            'w' => ['nullable', 'integer', 'min:50', 'max:2400'],
        ]);

        $tempat = $this->temukan($fitur, $slug, hanyaAktif: false);
        $photo = $validated['photo'];

        if (
            $tempat->google_place_id === null
            || ! str_starts_with($photo, 'places/')
            || ! str_contains($photo, $tempat->google_place_id)
        ) {
            abort(404);
        }

        $hasil = $lookup->foto($photo, (int) ($validated['w'] ?? 1200));

        if ($hasil === null) {
            abort(404);
        }

        return response($hasil['body'], 200)
            ->header('Content-Type', $hasil['content_type'])
            ->header('Cache-Control', 'public, max-age=86400');
    }

    private function temukan(string $fitur, string $slug, bool $hanyaAktif = true): Model
    {
        abort_unless(isset(self::MODEL[$fitur]), 404);

        $query = self::MODEL[$fitur]::query()->where('slug', $slug);

        if ($hanyaAktif) {
            $query->where('status', 'aktif');
        }

        return $query->firstOrFail();
    }
}
