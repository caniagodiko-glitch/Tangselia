<?php

namespace Database\Factories;

use App\Models\Sumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sumber>
 */
class SumberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->company().' News',
            'tipe' => 'berita',
            'jenis' => fake()->randomElement(['rss', 'api', 'scraping']),
            'feed_url' => fake()->url(),
            'aktif' => true,
            'last_fetched_at' => null,
            'last_status' => null,
            'last_error' => null,
            'item_count' => 0,
        ];
    }

    /**
     * Indicate that the source feeds job postings.
     */
    public function lowongan(): static
    {
        return $this->state(fn (array $attributes) => [
            'nama' => fake()->unique()->company().' Loker',
            'tipe' => 'lowongan',
        ]);
    }
}
