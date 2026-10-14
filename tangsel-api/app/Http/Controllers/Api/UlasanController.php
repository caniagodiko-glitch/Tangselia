<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UlasanResource;
use App\Services\DirektoriRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class UlasanController extends Controller
{
    /**
     * Daftar ulasan warga untuk satu tempat (publik).
     */
    public function index(Request $request, string $fitur, string $slug): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $tempat = $this->temukan($fitur, $slug);

        $ulasan = $tempat->ulasan()
            ->where('status', 'terbit')
            ->with('user:id,nama')
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 10));

        return UlasanResource::collection($ulasan);
    }

    /**
     * Simpan ulasan warga (rating, komentar, maksimal 3 foto).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fitur' => ['required', Rule::in(DirektoriRegistry::fiturTerdaftar())],
            'slug' => ['required', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['required', 'string', 'min:5', 'max:2000'],
            'foto' => ['nullable', 'array', 'max:3'],
            'foto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $tempat = $this->temukan($validated['fitur'], $validated['slug']);

        $foto = [];

        foreach ($request->file('foto', []) as $berkas) {
            $foto[] = $berkas->store('ulasan', 'public');
        }

        $ulasan = $tempat->ulasan()->create([
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'komentar' => $validated['komentar'],
            'foto' => $foto === [] ? null : $foto,
            'status' => 'terbit',
        ]);

        return response()->json([
            'message' => 'Terima kasih, ulasanmu sudah tayang.',
            'data' => new UlasanResource($ulasan->load('user:id,nama')),
        ], 201);
    }

    private function temukan(string $fitur, string $slug): Model
    {
        if (! isset(DirektoriRegistry::MODEL[$fitur])) {
            abort(404);
        }

        $modelClass = DirektoriRegistry::model($fitur);

        return $modelClass::where('slug', $slug)->where('status', 'aktif')->firstOrFail();
    }
}
