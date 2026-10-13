<?php

namespace Database\Factories;

use App\Models\BeritaSumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeritaSumber>
 */
class BeritaSumberFactory extends Factory
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
            'jenis' => fake()->randomElement(['rss', 'api', 'scraping']),
            'feed_url' => fake()->url(),
            'aktif' => true,
            'last_fetched_at' => null,
            'last_status' => null,
            'last_error' => null,
            'item_count' => 0,
        ];
    }
}
