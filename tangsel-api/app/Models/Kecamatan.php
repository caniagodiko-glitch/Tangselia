<?php

namespace App\Models;

use Database\Factories\KecamatanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'slug', 'urutan'])]
class Kecamatan extends Model
{
    /** @use HasFactory<KecamatanFactory> */
    use HasFactory;

    protected $table = 'kecamatan';

    /** @return HasMany<Tempat, $this> */
    public function tempat(): HasMany
    {
        return $this->hasMany(Tempat::class);
    }

    /** @return HasMany<FasilitasOlahraga, $this> */
    public function fasilitasOlahraga(): HasMany
    {
        return $this->hasMany(FasilitasOlahraga::class);
    }

    /** @return HasMany<LayananKesehatan, $this> */
    public function layananKesehatan(): HasMany
    {
        return $this->hasMany(LayananKesehatan::class);
    }

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
