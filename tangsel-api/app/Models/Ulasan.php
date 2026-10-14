<?php

namespace App\Models;

use Database\Factories\UlasanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'direktori_type',
    'direktori_id',
    'user_id',
    'rating',
    'komentar',
    'foto',
    'status',
])]
class Ulasan extends Model
{
    /** @use HasFactory<UlasanFactory> */
    use HasFactory;

    protected $table = 'ulasan';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'foto' => 'array',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function direktori(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
