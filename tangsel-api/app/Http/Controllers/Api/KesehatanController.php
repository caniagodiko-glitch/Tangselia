<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LayananKesehatanResource;
use App\Models\Kecamatan;
use App\Models\LayananKesehatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class KesehatanController extends Controller
{
    /**
     * Daftar layanan kesehatan aktif: filter jenis, kecamatan, pencarian, cursor.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'jenis' => ['nullable', 'in:apotek,rumah_sakit,puskesmas,klinik,posyandu'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = LayananKesehatan::query()
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

        $layanan = $query
            ->orderBy('nama')
            ->cursorPaginate((int) ($validated['per_page'] ?? 12))
            ->withQueryString();

        return LayananKesehatanResource::collection($layanan);
    }

    /**
     * Detail layanan kesehatan berdasarkan slug.
     */
    public function show(string $slug): LayananKesehatanResource
    {
        $layanan = LayananKesehatan::with('kecamatan:id,nama,slug')
            ->withCount(['ulasan as ulasan_jumlah' => fn ($q) => $q->where('status', 'terbit')])
            ->withSum(['ulasan as ulasan_total' => fn ($q) => $q->where('status', 'terbit')], 'rating')
            ->with(['ulasan' => fn ($q) => $q->where('status', 'terbit')->with('user:id,nama')->latest()->limit(5)])
            ->where('slug', $slug)
            ->where('status', 'aktif')
            ->firstOrFail();

        return new LayananKesehatanResource($layanan);
    }

    /**
     * Metadata untuk filter: jenis dan kecamatan beserta jumlahnya.
     */
    public function meta(): JsonResponse
    {
        $jenis = ['apotek', 'rumah_sakit', 'puskesmas', 'klinik', 'posyandu'];

        $jumlahPerJenis = LayananKesehatan::where('status', 'aktif')
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
                    ->withCount(['layananKesehatan as jumlah' => fn ($q) => $q->where('status', 'aktif')])
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
     * Tambah layanan kesehatan (admin).
     */
    public function store(Request $request): JsonResponse
    {
        $layanan = LayananKesehatan::create($this->validated($request));

        return response()->json([
            'data' => new LayananKesehatanResource($layanan->load('kecamatan:id,nama,slug')),
        ], 201);
    }

    /**
     * Ubah layanan kesehatan (admin).
     */
    public function update(Request $request, LayananKesehatan $layanan): LayananKesehatanResource
    {
        $data = $this->validated($request);
        unset($data['slug']);

        $layanan->update($data);

        return new LayananKesehatanResource($layanan->fresh('kecamatan:id,nama,slug'));
    }

    /**
     * Hapus layanan kesehatan (admin).
     */
    public function destroy(LayananKesehatan $layanan): JsonResponse
    {
        $layanan->delete();

        return response()->json(['message' => 'Layanan kesehatan dihapus.']);
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
            'jenis' => ['required', 'in:apotek,rumah_sakit,puskesmas,klinik,posyandu'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'alamat' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'website' => ['nullable', 'url', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'jam_buka' => ['nullable', 'date_format:H:i'],
            'jam_tutup' => ['nullable', 'date_format:H:i'],
            'layanan_24_jam' => ['nullable', 'boolean'],
            'fasilitas' => ['nullable', 'array'],
            'apotek' => ['nullable', 'array'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        if (is_string($validated['deskripsi'] ?? null) && trim((string) $validated['deskripsi']) !== '') {
            $validated['deskripsi_sumber'] = 'editor';
        }

        return $validated + [
            'slug' => Str::slug($validated['nama']),
        ];
    }
}
