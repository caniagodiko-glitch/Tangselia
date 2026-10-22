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

/**
 * Kurasi lowongan oleh administrator.
 *
 * Alur status: baru -> tayang (dipublikasikan) atau ditolak. Lowongan yang
 * tayang otomatis kedaluwarsa setelah config/lowongan.kedaluwarsa_hari hari
 * dan dipindahkan ke status kedaluwarsa oleh perintah lowongan:expire.
 */
class AdminLowonganController extends Controller
{
    /**
     * Transisi status yang sah.
     *
     * @var array<string, list<string>>
     */
    private const TRANSISI = [
        'baru' => ['tayang', 'ditolak'],
        'ditolak' => ['baru'],
        'tayang' => ['kedaluwarsa', 'baru'],
        'kedaluwarsa' => ['baru'],
    ];

    /**
     * Daftar lowongan untuk kurasi: seluruh status, dapat disaring.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['baru', 'tayang', 'ditolak', 'kedaluwarsa'])],
            'jenis' => ['nullable', Rule::in(['magang', 'karir', 'part-time'])],
            'relevan' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = Lowongan::with(['kategori:id,nama,slug', 'kecamatan:id,nama,slug'])
            ->orderByDesc('id');

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if (array_key_exists('relevan', $validated) && $validated['relevan'] !== null) {
            $query->where('relevan', (bool) $validated['relevan']);
        }

        if ($validated['jenis'] ?? null) {
            $query->where('jenis', $validated['jenis']);
        }

        if ($validated['q'] ?? null) {
            $term = '%'.$validated['q'].'%';
            $query->where(fn ($q) => $q
                ->where('judul', 'like', $term)
                ->orWhere('kode', 'like', $term)
                ->orWhere('perusahaan', 'like', $term));
        }

        $lowongan = $query
            ->cursorPaginate((int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return LowonganResource::collection($lowongan);
    }

    /**
     * Simpan hasil kurasi: koreksi field hasil ekstraksi dan/atau ubah status.
     */
    public function update(Request $request, Lowongan $lowongan): JsonResponse
    {
        $validated = $request->validate([
            'posisi' => ['nullable', 'string', 'max:255'],
            'perusahaan' => ['nullable', 'string', 'max:255'],
            'jenis' => ['nullable', Rule::in(['magang', 'karir', 'part-time'])],
            'deskripsi' => ['nullable', 'string', 'max:10000'],
            'kualifikasi' => ['nullable', 'string', 'max:10000'],
            'cara_apply' => ['nullable', 'string', 'max:5000'],
            'kategori_id' => ['nullable', Rule::exists('kategori', 'id')->where('scope', 'lowongan')],
            'kecamatan_id' => ['nullable', Rule::exists('kecamatan', 'id')],
            'relevan' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['baru', 'tayang', 'ditolak', 'kedaluwarsa'])],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = $validated['status'] ?? null;

        if ($target !== null && $target !== $lowongan->status) {
            if (! in_array($target, self::TRANSISI[$lowongan->status] ?? [], true)) {
                return response()->json([
                    'message' => "Transisi status dari \"{$lowongan->status}\" ke \"$target\" tidak valid.",
                ], 422);
            }

            $lowongan->status = $target;
        }

        if ($lowongan->status === 'tayang') {
            $lowongan->tayang_at = $lowongan->tayang_at ?? now();
            $lowongan->published_at = $lowongan->published_at ?? now();
            $lowongan->kedaluwarsa_at = now()->addDays((int) config('lowongan.kedaluwarsa_hari', 30));
        } else {
            $lowongan->tayang_at = null;
            $lowongan->kedaluwarsa_at = null;
            $lowongan->published_at = null;
        }

        $data = collect($validated)->except('status')->all();

        $lowongan->fill($data);
        $lowongan->save();

        return response()->json([
            'data' => new LowonganResource($lowongan->fresh(['kategori:id,nama,slug', 'kecamatan:id,nama,slug'])),
        ]);
    }

    /**
     * Hapus lowongan yang tidak relevan.
     */
    public function destroy(Lowongan $lowongan): JsonResponse
    {
        $lowongan->delete();

        return response()->json(['message' => 'Lowongan dihapus.']);
    }

    /**
     * Metadata untuk form kurasi: kategori bidang dan kecamatan.
     */
    public function meta(): JsonResponse
    {
        return response()->json([
            'data' => [
                'kategori' => Kategori::where('scope', 'lowongan')
                    ->orderBy('urutan')
                    ->get(['id', 'nama', 'slug']),
                'kecamatan' => Kecamatan::orderBy('urutan')
                    ->get(['id', 'nama', 'slug']),
            ],
        ]);
    }
}
