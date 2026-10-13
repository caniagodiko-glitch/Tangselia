<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanResource extends JsonResource
{
    /**
     * Representasi laporan warga. Detail (pelapor, catatan admin) hanya
     * tampil untuk pemilik laporan atau admin; feed publik dibersihkan.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $private = $user !== null && ($user->role === 'admin' || $user->id === $this->pelapor_id);

        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'kategori' => $this->whenLoaded('kategori', fn () => [
                'id' => $this->kategori->id,
                'nama' => $this->kategori->nama,
                'slug' => $this->kategori->slug,
                'ikon' => $this->kategori->ikon,
            ]),
            'kecamatan' => $this->whenLoaded('kecamatan', fn () => [
                'id' => $this->kecamatan->id,
                'nama' => $this->kecamatan->nama,
                'slug' => $this->kecamatan->slug,
            ]),
            'foto' => $this->whenLoaded('foto', fn () => $this->foto->map(fn ($foto) => asset('storage/'.$foto->gambar))),
            'pelapor' => $private && $this->relationLoaded('pelapor')
                ? ['id' => $this->pelapor->id, 'nama' => $this->pelapor->nama]
                : null,
            'catatan_admin' => $private ? $this->catatan_admin : null,
            'diselesaikan_at' => $this->diselesaikan_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
