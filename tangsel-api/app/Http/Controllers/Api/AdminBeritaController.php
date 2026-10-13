<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BeritaResource;
use App\Models\Berita;
use App\Models\BeritaSumber;
use App\Services\NewsFetcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminBeritaController extends Controller
{
    /**
     * Tulis berita editorial (admin) — judul, konten, kategori,
     * kecamatan, unggah gambar.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);

        $berita = Berita::create($this->attributes($request, $validated) + [
            'slug' => $this->uniqueSlug($validated['judul']),
            'status' => $validated['status'] ?? 'draft',
            'published_at' => ($validated['status'] ?? 'draft') === 'published' ? now() : null,
        ]);

        return response()->json([
            'data' => new BeritaResource($berita->load('kategori:id,nama,slug', 'kecamatan:id,nama,slug')),
        ], 201);
    }

    /**
     * Ubah berita editorial (admin). Slug dipertahankan agar tautan
     * yang sudah dibagikan tetap hidup.
     */
    public function update(Request $request, Berita $berita): JsonResponse
    {
        if ($berita->tipe !== 'editorial') {
            return response()->json(['message' => 'Hanya berita editorial yang dapat diubah.'], 403);
        }

        $validated = $this->validated($request);
        $attributes = $this->attributes($request, $validated);

        if (array_key_exists('status', $validated)) {
            $attributes['status'] = $validated['status'];

            if ($validated['status'] === 'published' && $berita->published_at === null) {
                $attributes['published_at'] = now();
            }
        }

        $berita->update($attributes);

        return response()->json([
            'data' => new BeritaResource($berita->fresh('kategori:id,nama,slug', 'kecamatan:id,nama,slug')),
        ]);
    }

    /**
     * Hapus berita editorial (admin).
     */
    public function destroy(Berita $berita): JsonResponse
    {
        if ($berita->tipe !== 'editorial') {
            return response()->json(['message' => 'Hanya berita editorial yang dapat dihapus.'], 403);
        }

        if ($berita->gambar && ! str_starts_with($berita->gambar, 'http')) {
            Storage::disk('public')->delete($berita->gambar);
        }

        $berita->delete();

        return response()->json(['message' => 'Berita dihapus.']);
    }

    /**
     * Pemicu manual pengambilan berita agregat (§6.6) — mengembalikan
     * statistik proses dari seluruh sumber aktif.
     */
    public function refresh(NewsFetcher $fetcher): JsonResponse
    {
        return response()->json(['data' => $fetcher->run()]);
    }

    /**
     * Pemantauan kesehatan sumber RSS (§6.5).
     */
    public function sumber(): JsonResponse
    {
        return response()->json([
            'data' => BeritaSumber::orderBy('nama')->get(),
        ]);
    }

    /**
     * Aturan validasi bersama store dan update.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'konten' => ['required', 'string'],
            'ringkasan' => ['nullable', 'string', 'max:1000'],
            'kategori_id' => ['required', 'exists:kategori,id'],
            'kecamatan_id' => ['nullable', 'exists:kecamatan,id'],
            'gambar' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'in:draft,published,archived'],
        ]);
    }

    /**
     * Gabungan hasil validasi dengan data turunan editorial.
     * Kolom `status` sengaja tidak disertakan agar pembaruan parsial
     * tidak mengubah status yang sudah ada.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(Request $request, array $validated): array
    {
        unset($validated['status']);

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $request->file('gambar')->store('berita', 'public');
        } else {
            unset($validated['gambar']);
        }

        return $validated + [
            'tipe' => 'editorial',
            'editor_id' => $request->user()->id,
            'diringkas_oleh' => 'editor',
        ];
    }

    private function uniqueSlug(string $judul): string
    {
        $slug = Str::slug($judul) ?: 'berita-'.Str::lower(Str::random(8));
        $basis = $slug;
        $counter = 1;

        while (Berita::where('slug', $slug)->exists()) {
            $slug = $basis.'-'.$counter++;
        }

        return $slug;
    }
}
