<?php

namespace App\Models;

use Database\Factories\BeritaSumberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama',
    'jenis',
    'feed_url',
    'aktif',
    'last_fetched_at',
    'last_status',
    'last_error',
    'item_count',
])]
class BeritaSumber extends Model
{
    /** @use HasFactory<BeritaSumberFactory> */
    use HasFactory;

    protected $table = 'berita_sumber';

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
}
