<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use App\Models\LayananKesehatan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LayananKesehatan>
 */
class LayananKesehatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(['apotek', 'rumah_sakit', 'puskesmas', 'klinik', 'posyandu']);
        $nama = fake()->unique()->company();

        return [
            'kecamatan_id' => Kecamatan::factory(),
            'jenis' => $jenis,
            'nama' => $nama,
            'slug' => Str::slug($nama),
            'deskripsi' => fake()->paragraph(),
            'alamat' => fake()->address(),
            'latitude' => fake()->randomFloat(7, -6.40, -6.25),
            'longitude' => fake()->randomFloat(7, 106.65, 106.80),
            'telepon' => fake()->numerify('(021) ####-####'),
            'jam_buka' => fake()->randomElement(['07:00', '08:00']),
            'jam_tutup' => fake()->randomElement(['20:00', '21:00']),
            'layanan_24_jam' => fake()->boolean(40),
            'fasilitas' => fake()->randomElements(
                ['IGD', 'Poliklinik', 'Laboratorium', 'Ambulans', 'Parkir'],
                fake()->numberBetween(2, 4)
            ),
            'apotek' => $jenis === 'apotek' ? [
                'jam_buka' => '08:00-22:00',
                'layanan_resep' => true,
                'nomor_izin' => 'SIHA-'.fake()->numerify('####-####'),
            ] : null,
            'status' => 'aktif',
        ];
    }
}
