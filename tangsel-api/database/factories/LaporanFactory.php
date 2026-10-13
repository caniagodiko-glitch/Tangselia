<?php

namespace Database\Factories;

use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laporan>
 */
class LaporanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => 'LPR-'.fake()->unique()->numerify('#####'),
            'kategori_id' => Kategori::factory()->laporan(),
            'pelapor_id' => User::factory(),
            'kecamatan_id' => Kecamatan::factory(),
            'judul' => fake()->sentence(6),
            'deskripsi' => fake()->paragraph(),
            'latitude' => fake()->randomFloat(7, -6.40, -6.25),
            'longitude' => fake()->randomFloat(7, 106.65, 106.80),
            'status' => 'baru',
            'catatan_admin' => null,
            'diselesaikan_at' => null,
        ];
    }

    /**
     * Indicate that the report has been verified by an administrator.
     */
    public function diverifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'diverifikasi',
        ]);
    }

    /**
     * Indicate that the report is being handled.
     */
    public function diproses(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'diproses',
        ]);
    }

    /**
     * Indicate that the report has been completed.
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
            'diselesaikan_at' => now(),
        ]);
    }
}
