<?php

namespace App\Models;

use Database\Factories\FasilitasOlahragaFactory;
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
    'kapasitas',
    'tarif_sewa',
    'fasilitas',
    'rating',
    'gambar',
    'status',
])]
class FasilitasOlahraga extends Model
{
    /** @use HasFactory<FasilitasOlahragaFactory> */
    use HasFactory;

    protected $table = 'fasilitas_olahraga';

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
        ];
    }

    /** @return BelongsTo<Kecamatan, $this> */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
