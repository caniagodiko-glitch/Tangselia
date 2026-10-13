<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use App\Models\Tempat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tempat>
 */
class TempatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->company();

        return [
            'kecamatan_id' => Kecamatan::factory(),
            'jenis' => fake()->randomElement(['kafe', 'warkop', 'restoran']),
            'nama' => $nama,
            'slug' => Str::slug($nama),
            'deskripsi' => fake()->paragraph(),
            'alamat' => fake()->address(),
            'latitude' => fake()->randomFloat(7, -6.40, -6.25),
            'longitude' => fake()->randomFloat(7, 106.65, 106.80),
            'jam_buka' => fake()->randomElement(['07:00', '08:00', '09:00', '10:00']),
            'jam_tutup' => fake()->randomElement(['21:00', '22:00', '23:00']),
            'rentang_harga' => fake()->randomElement([
                '< Rp25.000',
                'Rp25.000 - Rp50.000',
                '> Rp50.000',
            ]),
            'rating' => fake()->randomFloat(2, 3.5, 5.0),
            'rating_kount' => fake()->numberBetween(5, 300),
            'fasilitas' => fake()->randomElements(
                ['WiFi Cepat', 'Stop Kontak', 'Area Smoking', 'Mushola', 'Parkir Luas'],
                fake()->numberBetween(2, 5)
            ),
            'menu' => [
                ['nama' => 'Kopi Susu', 'harga' => 'Rp18.000'],
                ['nama' => 'Nasi Goreng', 'harga' => 'Rp25.000'],
            ],
            'gambar' => null,
            'status' => 'aktif',
        ];
    }
}
