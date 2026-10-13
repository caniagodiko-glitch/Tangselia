<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeritaResource extends JsonResource
{
    /**
     * Representasi berita untuk konsumsi front-end.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipe' => $this->tipe,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'ringkasan' => $this->ringkasan,
            'diringkas_oleh' => $this->diringkas_oleh,
            'konten' => $this->konten,
            'url_asli' => $this->url_asli,
            'sumber' => $this->sumber,
            'sumber_url' => $this->sumber_url,
            'gambar' => $this->image($this->gambar),
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
            'editor' => $this->whenLoaded('editor', fn () => [
                'id' => $this->editor->id,
                'nama' => $this->editor->nama,
            ]),
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Gambar hasil unggahan berupa path lokal; gambar agregat sering
     * berupa URL eksternal dari feed sehingga dikirim apa adanya.
     */
    private function image(?string $gambar): ?string
    {
        if ($gambar === null || $gambar === '') {
            return null;
        }

        if (str_starts_with($gambar, 'http://') || str_starts_with($gambar, 'https://')) {
            return $gambar;
        }

        return asset('storage/'.$gambar);
    }
}
