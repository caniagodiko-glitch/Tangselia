<?php

namespace App\Models;

use Database\Factories\SumberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nama',
    'tipe',
    'jenis',
    'feed_url',
    'aktif',
    'last_fetched_at',
    'last_status',
    'last_error',
    'item_count',
])]
class Sumber extends Model
{
    /** @use HasFactory<SumberFactory> */
    use HasFactory;

    protected $table = 'sumber';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    /** @return HasMany<Lowongan, $this> */
    public function lowongan(): HasMany
    {
        return $this->hasMany(Lowongan::class);
    }
}
