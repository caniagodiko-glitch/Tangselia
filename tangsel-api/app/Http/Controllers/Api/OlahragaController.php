<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FasilitasOlahragaResource;
use App\Models\FasilitasOlahraga;
use App\Models\Kecamatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class OlahragaController extends Controller
{
    /**
     * Daftar fasilitas olahraga aktif: filter jenis, kecamatan, pencarian, cursor.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'jenis' => ['nullable', 'in:gor_bulutangkis,lapangan_futsal,kolam_renang,lapangan_basket,gym'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = FasilitasOlahraga::query()
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

        $fasilitas = $query
            ->orderBy('nama')
            ->cursorPaginate((int) ($validated['per_page'] ?? 12))
            ->withQueryString();

        return FasilitasOlahragaResource::collection($fasilitas);
    }

    /**
     * Detail fasilitas berdasarkan slug.
     */
    public function show(string $slug): FasilitasOlahragaResource
    {
        $fasilitas = FasilitasOlahraga::with('kecamatan:id,nama,slug')
            ->withCount(['ulasan as ulasan_jumlah' => fn ($q) => $q->where('status', 'terbit')])
            ->withSum(['ulasan as ulasan_total' => fn ($q) => $q->where('status', 'terbit')], 'rating')
            ->with(['ulasan' => fn ($q) => $q->where('status', 'terbit')->with('user:id,nama')->latest()->limit(5)])
            ->where('slug', $slug)
            ->where('status', 'aktif')
            ->firstOrFail();

        return new FasilitasOlahragaResource($fasilitas);
    }

    /**
     * Metadata untuk filter: jenis dan kecamatan beserta jumlahnya.
     */
    public function meta(): JsonResponse
    {
        $jenis = ['gor_bulutangkis', 'lapangan_futsal', 'kolam_renang', 'lapangan_basket', 'gym'];

        $jumlahPerJenis = FasilitasOlahraga::where('status', 'aktif')
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
                    ->withCount(['fasilitasOlahraga as jumlah' => fn ($q) => $q->where('status', 'aktif')])
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
     * Tambah fasilitas olahraga (admin).
     */
    public function store(Request $request): JsonResponse
    {
        $fasilitas = FasilitasOlahraga::create($this->validated($request));

        return response()->json([
            'data' => new FasilitasOlahragaResource($fasilitas->load('kecamatan:id,nama,slug')),
        ], 201);
    }

    /**
     * Ubah fasilitas olahraga (admin).
     */
    public function update(Request $request, FasilitasOlahraga $fasilitas): FasilitasOlahragaResource
    {
        $data = $this->validated($request);
        unset($data['slug']);

        $fasilitas->update($data);

        return new FasilitasOlahragaResource($fasilitas->fresh('kecamatan:id,nama,slug'));
    }

    /**
     * Hapus fasilitas olahraga (admin).
     */
    public function destroy(FasilitasOlahraga $fasilitas): JsonResponse
    {
        $fasilitas->delete();

        return response()->json(['message' => 'Fasilitas olahraga dihapus.']);
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
            'jenis' => ['required', 'in:gor_bulutangkis,lapangan_futsal,kolam_renang,lapangan_basket,gym'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'alamat' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'website' => ['nullable', 'url', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'kapasitas' => ['nullable', 'integer', 'min:0'],
            'tarif_sewa' => ['nullable', 'string', 'max:64'],
            'fasilitas' => ['nullable', 'array'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'gambar' => ['nullable', 'string', 'max:255'],
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
