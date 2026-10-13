<?php

namespace Database\Factories;

use App\Models\FasilitasOlahraga;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FasilitasOlahraga>
 */
class FasilitasOlahragaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->company().' Sports';

        return [
            'kecamatan_id' => Kecamatan::factory(),
            'jenis' => fake()->randomElement([
                'gor_bulutangkis',
                'lapangan_futsal',
                'kolam_renang',
                'lapangan_basket',
                'gym',
            ]),
            'nama' => $nama,
            'slug' => Str::slug($nama),
            'deskripsi' => fake()->paragraph(),
            'alamat' => fake()->address(),
            'latitude' => fake()->randomFloat(7, -6.40, -6.25),
            'longitude' => fake()->randomFloat(7, 106.65, 106.80),
            'kapasitas' => fake()->numberBetween(10, 100),
            'tarif_sewa' => fake()->randomElement([
                'Rp20.000/jam',
                'Rp35.000/jam',
                'Rp50.000/sesi',
            ]),
            'fasilitas' => fake()->randomElements(
                ['Bola', 'Raket', 'Lapangan', 'Tempat Mandi', 'Kantin', 'Parkir'],
                fake()->numberBetween(2, 4)
            ),
            'rating' => fake()->randomFloat(2, 3.5, 5.0),
            'gambar' => null,
            'status' => 'aktif',
        ];
    }
}
