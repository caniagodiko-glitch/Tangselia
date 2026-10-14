<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UlasanResource extends JsonResource
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
            'rating' => $this->rating,
            'komentar' => $this->komentar,
            'foto' => $this->foto,
            'penulis' => $this->whenLoaded('user', fn () => $this->user?->nama),
            'status' => $this->status,
            'dibuat_pada' => $this->created_at,
        ];
    }
}
