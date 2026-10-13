<?php

namespace App\Models;

use Database\Factories\BeritaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kategori_id',
    'kecamatan_id',
    'editor_id',
    'tipe',
    'judul',
    'slug',
    'ringkasan',
    'diringkas_oleh',
    'konten',
    'url_asli',
    'sumber',
    'sumber_url',
    'gambar',
    'status',
    'published_at',
    'fetched_at',
])]
class Berita extends Model
{
    /** @use HasFactory<BeritaFactory> */
    use HasFactory;

    protected $table = 'berita';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
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

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    /** @param Builder<Berita> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->orderByDesc('published_at');
    }
}
