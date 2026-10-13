<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            KecamatanSeeder::class,
            KategoriSeeder::class,
            UserSeeder::class,
            BeritaSumberSeeder::class,
            TempatSeeder::class,
            FasilitasOlahragaSeeder::class,
            LayananKesehatanSeeder::class,
            BeritaSeeder::class,
            LaporanSeeder::class,
        ]);
    }
}
