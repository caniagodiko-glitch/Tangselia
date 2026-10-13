<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TempatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'sumber' => $this->sumber,
            'osm_id' => $this->osm_id,
            'google_place_id' => $this->google_place_id,
            'jenis' => $this->jenis,
            'deskripsi' => $this->deskripsi,
            'alamat' => $this->alamat,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'kecamatan' => $this->whenLoaded('kecamatan', fn () => [
                'nama' => $this->kecamatan->nama,
                'slug' => $this->kecamatan->slug,
            ]),
            'jam_buka' => $this->jam_buka,
            'jam_tutup' => $this->jam_tutup,
            'jam_buka_teks' => $this->jam_buka_teks,
            'rentang_harga' => $this->rentang_harga,
            'rating' => $this->rating,
            'rating_kount' => $this->rating_kount,
            'fasilitas' => $this->fasilitas,
            'menu' => $this->menu,
            'gambar' => $this->gambar,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
