<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayananKesehatanResource extends JsonResource
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
            'deskripsi_sumber' => $this->deskripsi_sumber,
            'alamat' => $this->alamat,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'kecamatan' => $this->whenLoaded('kecamatan', fn () => [
                'nama' => $this->kecamatan->nama,
                'slug' => $this->kecamatan->slug,
            ]),
            'website' => $this->website,
            'telepon' => $this->telepon,
            'jam_buka' => $this->jam_buka,
            'jam_tutup' => $this->jam_tutup,
            'jam_buka_teks' => $this->jam_buka_teks,
            'layanan_24_jam' => $this->layanan_24_jam,
            'fasilitas' => $this->fasilitas,
            'apotek' => $this->apotek,
            'status' => $this->status,
            'atribusi' => $this->atribusi(),
            'ulasan' => $this->ringkasanUlasan(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function atribusi(): array
    {
        if (is_array($this->atribusi) && $this->atribusi !== []) {
            return $this->atribusi;
        }

        return [[
            'jenis' => 'data',
            'sumber' => 'OpenStreetMap',
            'lisensi' => 'ODbL 1.0',
            'tautan' => 'https://www.openstreetmap.org/copyright',
            'peran' => 'alamat, lokasi, jam operasional, fasilitas',
        ]];
    }

    /**
     * @return array{rating: float|null, jumlah: int, daftar: list<array<string, mixed>>}
     */
    private function ringkasanUlasan(): array
    {
        $jumlah = (int) ($this->ulasan_jumlah ?? 0);
        $total = (float) ($this->ulasan_total ?? 0);
        $daftar = [];

        if ($this->relationLoaded('ulasan')) {
            $daftar = $this->ulasan->map(fn ($item) => [
                'id' => $item->id,
                'rating' => $item->rating,
                'komentar' => $item->komentar,
                'foto' => $item->foto,
                'penulis' => $item->user?->nama,
                'dibuat_pada' => $item->created_at,
            ])->all();
        }

        return [
            'rating' => $jumlah > 0 ? round($total / $jumlah, 1) : null,
            'jumlah' => $jumlah,
            'daftar' => $daftar,
        ];
    }
}
