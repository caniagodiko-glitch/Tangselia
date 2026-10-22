<?php

namespace App\Models;

use Database\Factories\LowonganFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sumber_id',
    'kategori_id',
    'kecamatan_id',
    'kode',
    'judul',
    'slug',
    'judul_hash',
    'posisi',
    'perusahaan',
    'jenis',
    'deskripsi',
    'kualifikasi',
    'cara_apply',
    'url_asli',
    'sumber',
    'sumber_url',
    'status',
    'relevan',
    'catatan_admin',
    'tayang_at',
    'kedaluwarsa_at',
    'published_at',
    'fetched_at',
])]
class Lowongan extends Model
{
    /** @use HasFactory<LowonganFactory> */
    use HasFactory;

    protected $table = 'lowongan';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relevan' => 'boolean',
            'tayang_at' => 'datetime',
            'kedaluwarsa_at' => 'datetime',
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sumber, $this> */
    public function sumberRelasi(): BelongsTo
    {
        return $this->belongsTo(Sumber::class, 'sumber_id');
    }

    /** @return BelongsTo<Kategori, $this> */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /** @return BelongsTo<Kecamatan, $this> */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    /**
     * Lowongan yang sudah tayang dan belum kedaluwarsa.
     *
     * @param  Builder<Lowongan>  $query
     * @return Builder<Lowongan>
     */
    public function scopeTayang(Builder $query): Builder
    {
        return $query->where('status', 'tayang')
            ->where(fn (Builder $inner) => $inner
                ->whereNull('kedaluwarsa_at')
                ->orWhere('kedaluwarsa_at', '>', now()));
    }

    /**
     * @param  Builder<Lowongan>  $query
     * @return Builder<Lowongan>
     */
    public function scopeTervalidasi(Builder $query): Builder
    {
        return $query->whereIn('status', ['tayang', 'kedaluwarsa']);
    }
}
