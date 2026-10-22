<?php

namespace Database\Factories;

use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Lowongan;
use App\Models\Sumber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lowongan>
 */
class LowonganFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $posisi = fake()->randomElement([
            'Front Office',
            'Barista',
            'Staff Administration',
            'Junior Developer',
            'Sales Executive',
        ]);

        $perusahaan = fake()->company();

        return [
            'sumber_id' => Sumber::factory(),
            'kategori_id' => Kategori::factory()->lowongan(),
            'kecamatan_id' => Kecamatan::factory(),
            'kode' => 'LGN-'.fake()->unique()->numerify('########'),
            'judul' => $posisi.' - '.$perusahaan,
            'slug' => Str::slug($posisi.' '.$perusahaan).'-'.fake()->unique()->numberBetween(1, 999999),
            'judul_hash' => sha1(fake()->unique()->uuid()),
            'posisi' => $posisi,
            'perusahaan' => $perusahaan,
            'jenis' => fake()->randomElement(['magang', 'karir', 'part-time']),
            'deskripsi' => fake()->paragraph(),
            'kualifikasi' => fake()->paragraph(),
            'cara_apply' => 'Kirim lamaran ke surel resmi perusahaan.',
            'url_asli' => fake()->url(),
            'sumber' => fake()->company().' News',
            'sumber_url' => fake()->url(),
            'status' => 'baru',
            'relevan' => true,
            'catatan_admin' => null,
            'tayang_at' => null,
            'kedaluwarsa_at' => null,
            'published_at' => null,
            'fetched_at' => now(),
        ];
    }

    /**
     * Indicate that the posting has been curated and published.
     */
    public function tayang(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'tayang',
            'tayang_at' => now(),
            'published_at' => now(),
            'kedaluwarsa_at' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the posting has been rejected by an administrator.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ditolak',
            'catatan_admin' => fake()->sentence(),
        ]);
    }
}
