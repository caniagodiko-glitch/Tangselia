<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Kategori>
 */
class KategoriFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->words(2, true);

        return [
            'nama' => Str::title($nama),
            'slug' => Str::slug($nama),
            'scope' => 'berita',
            'ikon' => null,
            'urutan' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * Indicate that the category belongs to the report scope.
     */
    public function laporan(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'laporan',
        ]);
    }
}
