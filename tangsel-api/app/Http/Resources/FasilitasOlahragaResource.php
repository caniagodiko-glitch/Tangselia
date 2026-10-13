<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FasilitasOlahragaResource extends JsonResource
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
            'kapasitas' => $this->kapasitas,
            'tarif_sewa' => $this->tarif_sewa,
            'jam_buka_teks' => $this->jam_buka_teks,
            'fasilitas' => $this->fasilitas,
            'rating' => $this->rating,
            'gambar' => $this->gambar,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
