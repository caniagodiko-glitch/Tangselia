<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LowonganResource;
use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Lowongan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class LowonganController extends Controller
{
    /**
     * Daftar lowongan yang sudah tayang: filter jenis, kategori, kecamatan,
     * dan pencarian, memakai cursor pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'jenis' => ['nullable', Rule::in(['magang', 'karir', 'part-time'])],
            'kategori' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = Lowongan::tayang()
            ->with(['kategori:id,nama,slug', 'kecamatan:id,nama,slug']);

        if ($validated['jenis'] ?? null) {
            $query->where('jenis', $validated['jenis']);
        }

        if ($validated['kategori'] ?? null) {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $validated['kategori']));
        }

        if ($validated['kecamatan'] ?? null) {
            $query->whereHas('kecamatan', fn ($q) => $q->where('slug', $validated['kecamatan']));
        }

        if ($validated['q'] ?? null) {
            $term = '%'.$validated['q'].'%';
            $query->where(fn ($q) => $q
                ->where('judul', 'like', $term)
                ->orWhere('posisi', 'like', $term)
                ->orWhere('perusahaan', 'like', $term));
        }

        $lowongan = $query
            ->orderByDesc('id')
            ->cursorPaginate((int) ($validated['per_page'] ?? 12))
            ->withQueryString();

        return LowonganResource::collection($lowongan);
    }

    /**
     * Lowongan terbaru untuk section beranda.
     */
    public function terbaru(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $lowongan = Lowongan::tayang()
            ->with(['kategori:id,nama,slug', 'kecamatan:id,nama,slug'])
            ->orderByDesc('id')
            ->limit((int) ($validated['limit'] ?? 5))
            ->get();

        return LowonganResource::collection($lowongan);
    }

    /**
     * Metadata untuk filter lowongan: kategori bidang, kecamatan, jenis.
     */
    public function meta(): JsonResponse
    {
        $jenisCounts = Lowongan::tayang()
            ->reorder()
            ->selectRaw('jenis, COUNT(*) as jumlah')
            ->groupBy('jenis')
            ->pluck('jumlah', 'jenis');

        return response()->json([
            'data' => [
                'kategori' => Kategori::where('scope', 'lowongan')
                    ->orderBy('urutan')
                    ->withCount(['lowongan as jumlah' => fn ($q) => $q->tayang()])
                    ->get(['id', 'nama', 'slug'])
                    ->map(fn ($kategori) => [
                        'nama' => $kategori->nama,
                        'slug' => $kategori->slug,
                        'jumlah' => (int) $kategori->jumlah,
                    ]),
                'kecamatan' => Kecamatan::orderBy('urutan')
                    ->withCount(['lowongan as jumlah' => fn ($q) => $q->tayang()])
                    ->get(['id', 'nama', 'slug'])
                    ->map(fn ($kecamatan) => [
                        'nama' => $kecamatan->nama,
                        'slug' => $kecamatan->slug,
                        'jumlah' => (int) $kecamatan->jumlah,
                    ]),
                'jenis' => array_map(fn (string $value) => [
                    'value' => $value,
                    'jumlah' => (int) ($jenisCounts[$value] ?? 0),
                ], ['magang', 'karir', 'part-time']),
            ],
        ]);
    }

    /**
     * Detail lowongan berdasarkan slug.
     */
    public function show(string $slug): LowonganResource
    {
        $lowongan = Lowongan::tayang()
            ->with(['kategori:id,nama,slug', 'kecamatan:id,nama,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new LowonganResource($lowongan);
    }
}
