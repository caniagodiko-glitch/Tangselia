<?php

namespace App\Models;

use Database\Factories\LayananKesehatanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
    'deskripsi_sumber',
    'alamat',
    'latitude',
    'longitude',
    'website',
    'telepon',
    'jam_buka',
    'jam_tutup',
    'layanan_24_jam',
    'fasilitas',
    'apotek',
    'status',
    'atribusi',
    'info_diunduh_pada',
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
            'atribusi' => 'array',
        ];
    }

    /** @return BelongsTo<Kecamatan, $this> */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    /** @return MorphMany<Ulasan, $this> */
    public function ulasan(): MorphMany
    {
        return $this->morphMany(Ulasan::class, 'direktori');
    }
}
