<?php

namespace Database\Factories;

use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Berita>
 */
class BeritaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->unique()->sentence(6);

        return [
            'kategori_id' => Kategori::factory(),
            'kecamatan_id' => Kecamatan::factory(),
            'editor_id' => User::factory(),
            'tipe' => 'editorial',
            'judul' => $judul,
            'slug' => Str::slug($judul).'-'.fake()->unique()->numberBetween(1, 999999),
            'ringkasan' => fake()->paragraph(),
            'diringkas_oleh' => 'editor',
            'konten' => fake()->paragraphs(3, true),
            'url_asli' => null,
            'sumber' => null,
            'sumber_url' => null,
            'gambar' => null,
            'status' => 'published',
            'published_at' => now(),
            'fetched_at' => null,
        ];
    }

    /**
     * Indicate that the news item was aggregated from an external source.
     */
    public function agregat(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => 'agregat',
            'editor_id' => null,
            'konten' => null,
            'diringkas_oleh' => fake()->randomElement(['ai', 'feed']),
            'url_asli' => fake()->url(),
            'sumber' => fake()->company().' News',
            'sumber_url' => fake()->url(),
            'fetched_at' => now(),
        ]);
    }
}
