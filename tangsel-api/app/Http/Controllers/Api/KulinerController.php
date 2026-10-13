<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TempatResource;
use App\Models\Kecamatan;
use App\Models\Tempat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class KulinerController extends Controller
{
    /**
     * Daftar tempat kuliner aktif: filter jenis, kecamatan, pencarian, cursor.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'jenis' => ['nullable', 'in:kafe,warkop,restoran'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = Tempat::query()
            ->where('status', 'aktif')
            ->with('kecamatan:id,nama,slug');

        if ($validated['jenis'] ?? null) {
            $query->where('jenis', $validated['jenis']);
        }

        if ($validated['kecamatan'] ?? null) {
            $query->whereHas('kecamatan', fn ($q) => $q->where('slug', $validated['kecamatan']));
        }

        if ($validated['q'] ?? null) {
            $term = '%'.$validated['q'].'%';
            $query->where(fn ($q) => $q
                ->where('nama', 'like', $term)
                ->orWhere('deskripsi', 'like', $term)
                ->orWhere('alamat', 'like', $term));
        }

        $tempat = $query
            ->orderBy('nama')
            ->cursorPaginate((int) ($validated['per_page'] ?? 12))
            ->withQueryString();

        return TempatResource::collection($tempat);
    }

    /**
     * Detail tempat berdasarkan slug.
     */
    public function show(string $slug): TempatResource
    {
        $tempat = Tempat::with('kecamatan:id,nama,slug')
            ->where('slug', $slug)
            ->where('status', 'aktif')
            ->firstOrFail();

        return new TempatResource($tempat);
    }

    /**
     * Metadata untuk filter: jenis dan kecamatan beserta jumlahnya.
     */
    public function meta(): JsonResponse
    {
        $jenis = ['kafe', 'warkop', 'restoran'];

        $jumlahPerJenis = Tempat::where('status', 'aktif')
            ->selectRaw('jenis, COUNT(*) as jumlah')
            ->groupBy('jenis')
            ->pluck('jumlah', 'jenis');

        return response()->json([
            'data' => [
                'jenis' => array_map(fn (string $value) => [
                    'value' => $value,
                    'jumlah' => (int) ($jumlahPerJenis[$value] ?? 0),
                ], $jenis),
                'kecamatan' => Kecamatan::orderBy('urutan')
                    ->withCount(['tempat as jumlah' => fn ($q) => $q->where('status', 'aktif')])
                    ->get(['id', 'nama', 'slug'])
                    ->map(fn ($kecamatan) => [
                        'nama' => $kecamatan->nama,
                        'slug' => $kecamatan->slug,
                        'jumlah' => (int) $kecamatan->jumlah,
                    ]),
            ],
        ]);
    }

    /**
     * Tambah tempat (admin).
     */
    public function store(Request $request): JsonResponse
    {
        $tempat = Tempat::create($this->validated($request));

        return response()->json([
            'data' => new TempatResource($tempat->load('kecamatan:id,nama,slug')),
        ], 201);
    }

    /**
     * Ubah tempat (admin).
     */
    public function update(Request $request, Tempat $tempat): TempatResource
    {
        $data = $this->validated($request);
        unset($data['slug']);

        $tempat->update($data);

        return new TempatResource($tempat->fresh('kecamatan:id,nama,slug'));
    }

    /**
     * Hapus tempat (admin).
     */
    public function destroy(Tempat $tempat): JsonResponse
    {
        $tempat->delete();

        return response()->json(['message' => 'Tempat dihapus.']);
    }

    /**
     * Aturan validasi bersama store dan update.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'kecamatan_id' => ['required', 'exists:kecamatan,id'],
            'jenis' => ['required', 'in:kafe,warkop,restoran'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'alamat' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'jam_buka' => ['nullable', 'date_format:H:i'],
            'jam_tutup' => ['nullable', 'date_format:H:i'],
            'rentang_harga' => ['nullable', 'string', 'max:32'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'rating_kount' => ['nullable', 'integer', 'min:0'],
            'fasilitas' => ['nullable', 'array'],
            'menu' => ['nullable', 'array'],
            'gambar' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        return $validated + [
            'slug' => Str::slug($validated['nama']),
        ];
    }
}
