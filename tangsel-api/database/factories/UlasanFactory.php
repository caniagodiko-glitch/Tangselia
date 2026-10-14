<?php

namespace Database\Factories;

use App\Models\FasilitasOlahraga;
use App\Models\LayananKesehatan;
use App\Models\Tempat;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ulasan>
 */
class UlasanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'direktori_type' => Tempat::class,
            'direktori_id' => Tempat::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'komentar' => fake()->paragraph(),
            'foto' => null,
            'status' => 'terbit',
        ];
    }

    public function untukOlahraga(): static
    {
        return $this->state(fn () => [
            'direktori_type' => FasilitasOlahraga::class,
            'direktori_id' => FasilitasOlahraga::factory(),
        ]);
    }

    public function untukKesehatan(): static
    {
        return $this->state(fn () => [
            'direktori_type' => LayananKesehatan::class,
            'direktori_id' => LayananKesehatan::factory(),
        ]);
    }
}
