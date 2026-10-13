<?php

namespace Database\Factories;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanFoto>
 */
class LaporanFotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'laporan_id' => Laporan::factory(),
            'gambar' => 'laporan/'.fake()->uuid().'.jpg',
            'urutan' => fake()->numberBetween(0, 2),
        ];
    }
}
