<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BeritaResource;
use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Kecamatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BeritaController extends Controller
{
    /**
     * Daftar berita terbit: filter kategori, kecamatan, sumber, tipe,
     * pencarian, dan cursor (§6.7).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'kategori' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'sumber' => ['nullable', 'string', 'max:255'],
            'tipe' => ['nullable', 'in:editorial,agregat'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = Berita::published()
            ->with(['kategori:id,nama,slug', 'kecamatan:id,nama,slug']);

        if ($validated['kategori'] ?? null) {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $validated['kategori']));
        }

        if ($validated['kecamatan'] ?? null) {
            $query->whereHas('kecamatan', fn ($q) => $q->where('slug', $validated['kecamatan']));
        }

        if ($validated['sumber'] ?? null) {
            $query->where('sumber', $validated['sumber']);
        }

        if ($validated['tipe'] ?? null) {
            $query->where('tipe', $validated['tipe']);
        }

        if ($validated['q'] ?? null) {
            $term = '%'.$validated['q'].'%';
            $query->where(fn ($q) => $q
                ->where('judul', 'like', $term)
                ->orWhere('ringkasan', 'like', $term));
        }

        $berita = $query
            ->orderByDesc('id')
            ->cursorPaginate((int) ($validated['per_page'] ?? 12))
            ->withQueryString();

        return BeritaResource::collection($berita);
    }

    /**
     * Berita terbaru untuk section beranda.
     */
    public function terbaru(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $berita = Berita::published()
            ->with(['kategori:id,nama,slug', 'kecamatan:id,nama,slug'])
            ->orderByDesc('id')
            ->limit((int) ($validated['limit'] ?? 5))
            ->get();

        return BeritaResource::collection($berita);
    }

    /**
     * Detail berita berdasarkan slug.
     */
    public function show(string $slug): BeritaResource
    {
        $berita = Berita::published()
            ->with(['kategori:id,nama,slug', 'kecamatan:id,nama,slug', 'editor:id,nama'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new BeritaResource($berita);
    }

    /**
     * Metadata untuk filter berita: kategori, kecamatan, sumber, tipe.
     */
    public function meta(): JsonResponse
    {
        $tipeCounts = Berita::published()
            ->reorder()
            ->selectRaw('tipe, COUNT(*) as jumlah')
            ->groupBy('tipe')
            ->pluck('jumlah', 'tipe');

        $sumberCounts = Berita::published()
            ->reorder()
            ->whereNotNull('sumber')
            ->selectRaw('sumber, COUNT(*) as jumlah')
            ->groupBy('sumber')
            ->orderBy('sumber')
            ->get()
            ->map(fn ($row) => [
                'nama' => $row->sumber,
                'jumlah' => (int) $row->jumlah,
            ]);

        return response()->json([
            'data' => [
                'kategori' => Kategori::where('scope', 'berita')
                    ->orderBy('urutan')
                    ->withCount(['berita as jumlah' => fn ($q) => $q->published()])
                    ->get(['id', 'nama', 'slug'])
                    ->map(fn ($kategori) => [
                        'nama' => $kategori->nama,
                        'slug' => $kategori->slug,
                        'jumlah' => (int) $kategori->jumlah,
                    ]),
                'kecamatan' => Kecamatan::orderBy('urutan')
                    ->withCount(['berita as jumlah' => fn ($q) => $q->published()])
                    ->get(['id', 'nama', 'slug'])
                    ->map(fn ($kecamatan) => [
                        'nama' => $kecamatan->nama,
                        'slug' => $kecamatan->slug,
                        'jumlah' => (int) $kecamatan->jumlah,
                    ]),
                'tipe' => array_map(fn (string $value) => [
                    'value' => $value,
                    'jumlah' => (int) ($tipeCounts[$value] ?? 0),
                ], ['editorial', 'agregat']),
                'sumber' => $sumberCounts,
            ],
        ]);
    }
}
