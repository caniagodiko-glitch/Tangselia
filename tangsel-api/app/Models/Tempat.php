<?php

namespace App\Models;

use Database\Factories\TempatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kecamatan_id',
    'jenis',
    'nama',
    'slug',
    'osm_id',
    'google_place_id',
    'sumber',
    'jam_buka_teks',
    'deskripsi',
    'alamat',
    'latitude',
    'longitude',
    'jam_buka',
    'jam_tutup',
    'rentang_harga',
    'rating',
    'rating_kount',
    'fasilitas',
    'menu',
    'gambar',
    'status',
])]
class Tempat extends Model
{
    /** @use HasFactory<TempatFactory> */
    use HasFactory;

    protected $table = 'tempat';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'rating' => 'float',
            'fasilitas' => 'array',
            'menu' => 'array',
        ];
    }

    /** @return BelongsTo<Kecamatan, $this> */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
