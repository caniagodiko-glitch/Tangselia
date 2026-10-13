<?php

namespace App\Models;

use Database\Factories\LaporanFotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['laporan_id', 'gambar', 'urutan'])]
class LaporanFoto extends Model
{
    /** @use HasFactory<LaporanFotoFactory> */
    use HasFactory;

    protected $table = 'laporan_foto';

    /** @return BelongsTo<Laporan, $this> */
    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }
}
