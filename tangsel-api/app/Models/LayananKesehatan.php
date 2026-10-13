<?php

namespace App\Models;

use Database\Factories\LayananKesehatanFactory;
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
    'telepon',
    'jam_buka',
    'jam_tutup',
    'layanan_24_jam',
    'fasilitas',
    'apotek',
    'status',
])]
class LayananKesehatan extends Model
{
    /** @use HasFactory<LayananKesehatanFactory> */
    use HasFactory;

    protected $table = 'layanan_kesehatan';

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
            'layanan_24_jam' => 'boolean',
            'fasilitas' => 'array',
            'apotek' => 'array',
        ];
    }

    /** @return BelongsTo<Kecamatan, $this> */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
