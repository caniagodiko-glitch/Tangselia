<?php

namespace App\Models;

use Database\Factories\KategoriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'slug', 'scope', 'ikon', 'urutan'])]
class Kategori extends Model
{
    /** @use HasFactory<KategoriFactory> */
    use HasFactory;

    protected $table = 'kategori';

    /** @return HasMany<Berita, $this> */
    public function berita(): HasMany
    {
        return $this->hasMany(Berita::class);
    }

    /** @return HasMany<Lowongan, $this> */
    public function lowongan(): HasMany
    {
        return $this->hasMany(Lowongan::class);
    }
}
