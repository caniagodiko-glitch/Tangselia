<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanResource;
use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Laporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LaporanController extends Controller
{
    private const FEED_STATUS = ['diverifikasi', 'diproses', 'selesai'];

    /**
     * Feed laporan untuk bagian beranda: hanya laporan dengan status
     * `diverifikasi` ke atas yang boleh tampil. Dengan parameter
     * `?mine=1` (wajib login) mengembalikan laporan milik pengguna.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'mine' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ]);

        $mine = (bool) ($validated['mine'] ?? false);

        // Route ini publik, jadi guard `sanctum` perlu di-resolve eksplisit
        // agar token Bearer tetap terbaca meski tanpa middleware auth.
        $user = auth('sanctum')->user() ?? $request->user();

        if ($mine && $user === null) {
            abort(401);
        }

        $query = Laporan::with(['foto', 'kategori:id,nama,slug,ikon', 'kecamatan:id,nama,slug']);

        $mine
            ? $query->where('pelapor_id', $user->id)
            : $query->whereIn('status', self::FEED_STATUS);

        $laporan = $query
            ->orderByDesc('created_at')
            ->cursorPaginate((int) ($validated['per_page'] ?? 12))
            ->withQueryString();

        return LaporanResource::collection($laporan);
    }

    /**
     * Metadata untuk form laporan: kategori (scope laporan) + kecamatan.
     */
    public function meta(): JsonResponse
    {
        return response()->json([
            'data' => [
                'kategori' => Kategori::where('scope', 'laporan')
                    ->orderBy('urutan')
                    ->get(['id', 'nama', 'slug', 'ikon']),
                'kecamatan' => Kecamatan::orderBy('urutan')
                    ->get(['id', 'nama', 'slug']),
            ],
        ]);
    }

    /**
     * Daftar laporan untuk admin (semua status) dengan filter status.
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['baru', 'diverifikasi', 'ditolak', 'diproses', 'selesai'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = Laporan::with(['foto', 'kategori:id,nama,slug,ikon', 'kecamatan:id,nama,slug'])
            ->orderByDesc('created_at');

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        $laporan = $query
            ->cursorPaginate((int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return LaporanResource::collection($laporan);
    }

    /**
     * Kirim laporan baru (warga) — multipart, maksimal tiga foto,
     * koordinat GPS, kode pelacakan unik.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => ['required', Rule::exists('kategori', 'id')->where('scope', 'laporan')],
            'kecamatan_id' => ['nullable', 'exists:kecamatan,id'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'min:20', 'max:5000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'foto' => ['nullable', 'array', 'max:3'],
            'foto.*' => ['image', 'max:2048'],
        ]);

        $laporan = Laporan::create([
            'kode' => $this->generateKode(),
            'kategori_id' => $validated['kategori_id'],
            'pelapor_id' => $request->user()->id,
            'kecamatan_id' => $validated['kecamatan_id'] ?? null,
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => 'baru',
        ]);

        foreach ($request->file('foto', []) as $index => $foto) {
            $laporan->foto()->create([
                'gambar' => $foto->store('laporan', 'public'),
                'urutan' => $index,
            ]);
        }

        return response()->json([
            'data' => new LaporanResource($laporan->load(['foto', 'kategori:id,nama,slug,ikon', 'kecamatan:id,nama,slug', 'pelapor:id,nama'])),
        ], 201);
    }

    /**
     * Detail laporan — hanya pemilik laporan atau admin yang melihatnya.
     */
    public function show(Request $request, string $kode): LaporanResource
    {
        $laporan = Laporan::with(['foto', 'kategori:id,nama,slug,ikon', 'kecamatan:id,nama,slug', 'pelapor:id,nama'])
            ->where('kode', $kode)
            ->firstOrFail();

        $this->authorize($request, $laporan);

        return new LaporanResource($laporan);
    }

    /**
     * Verifikasi laporan (admin): `diverifikasi` atau `ditolak`,
     * disertai catatan untuk pemohon.
     */
    public function verifikasi(Request $request, string $kode): JsonResponse
    {
        return $this->transisi($request, Laporan::where('kode', $kode)->firstOrFail(), 'verifikasi');
    }

    /**
     * Tindak lanjut (admin): `diproses` atau `selesai`.
     */
    public function status(Request $request, string $kode): JsonResponse
    {
        return $this->transisi($request, Laporan::where('kode', $kode)->firstOrFail(), 'status');
    }

    /**
     * Transisi status dengan alur dan urutan yang sah.
     */
    private function transisi(Request $request, Laporan $laporan, string $jenis): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in($jenis === 'verifikasi' ? ['diverifikasi', 'ditolak'] : ['diproses', 'selesai'])],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = $validated['status'];
        $current = $laporan->status;

        $sah = $jenis === 'verifikasi'
            ? $current === 'baru'
            : (($target === 'diproses' && $current === 'diverifikasi')
                || ($target === 'selesai' && $current === 'diproses')
                || ($target === 'selesai' && $current === 'diverifikasi'));

        if (! $sah) {
            return response()->json([
                'message' => "Transisi status dari \"$current\" ke \"$target\" tidak valid.",
            ], 422);
        }

        $laporan->update([
            'status' => $target,
            'catatan_admin' => $validated['catatan_admin'] ?? $laporan->catatan_admin,
            'diselesaikan_at' => $target === 'selesai' ? now() : $laporan->diselesaikan_at,
        ]);

        return response()->json([
            'data' => new LaporanResource($laporan->load(['foto', 'kategori:id,nama,slug,ikon', 'kecamatan:id,nama,slug', 'pelapor:id,nama'])),
        ]);
    }

    /**
     * Hanya pemilik laporan atau admin yang boleh melihat detail.
     */
    private function authorize(Request $request, Laporan $laporan): void
    {
        $user = $request->user();

        if ($user->role !== 'admin' && $laporan->pelapor_id !== $user->id) {
            abort(404);
        }
    }

    private function generateKode(): string
    {
        do {
            $kode = 'LAP-'.now()->format('Ymd').'-'.mb_strtoupper(Str::random(5));
        } while (Laporan::where('kode', $kode)->exists());

        return $kode;
    }
}
