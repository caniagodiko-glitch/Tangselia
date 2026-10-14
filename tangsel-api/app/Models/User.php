<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['nama', 'email', 'password', 'role', 'telepon'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** @return HasMany<Berita, $this> */
    public function berita(): HasMany
    {
        return $this->hasMany(Berita::class, 'editor_id');
    }

    /** @return HasMany<Laporan, $this> */
    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class, 'pelapor_id');
    }

    /** @return HasMany<Ulasan, $this> */
    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class);
    }
}
