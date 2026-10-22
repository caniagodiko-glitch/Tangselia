<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowonganResource extends JsonResource
{
    /**
     * Representasi lowongan kerja untuk konsumsi front-end.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'posisi' => $this->posisi,
            'perusahaan' => $this->perusahaan,
            'jenis' => $this->jenis,
            'deskripsi' => $this->deskripsi,
            'kualifikasi' => $this->kualifikasi,
            'cara_apply' => $this->cara_apply,
            'url_asli' => $this->url_asli,
            'sumber' => $this->sumber,
            'sumber_url' => $this->sumber_url,
            'kategori' => $this->whenLoaded('kategori', fn () => [
                'id' => $this->kategori->id,
                'nama' => $this->kategori->nama,
                'slug' => $this->kategori->slug,
            ]),
            'kecamatan' => $this->whenLoaded('kecamatan', fn () => [
                'id' => $this->kecamatan->id,
                'nama' => $this->kecamatan->nama,
                'slug' => $this->kecamatan->slug,
            ]),
            'status' => $this->status,
            'relevan' => $this->relevan,
            'catatan_admin' => $this->catatan_admin,
            'tayang_at' => $this->tayang_at?->toIso8601String(),
            'kedaluwarsa_at' => $this->kedaluwarsa_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
