<?php

namespace App\Models;

use Database\Factories\LaporanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode',
    'kategori_id',
    'pelapor_id',
    'kecamatan_id',
    'judul',
    'deskripsi',
    'latitude',
    'longitude',
    'status',
    'catatan_admin',
    'diselesaikan_at',
])]
class Laporan extends Model
{
    /** @use HasFactory<LaporanFactory> */
    use HasFactory;

    protected $table = 'laporan';

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
            'diselesaikan_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Kategori, $this> */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /** @return BelongsTo<User, $this> */
    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelapor_id');
    }

    /** @return BelongsTo<Kecamatan, $this> */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    /** @return HasMany<LaporanFoto, $this> */
    public function foto(): HasMany
    {
        return $this->hasMany(LaporanFoto::class);
    }
}
