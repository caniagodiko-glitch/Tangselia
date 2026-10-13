<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KecamatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatan = [
            'Ciputat',
            'Ciputat Timur',
            'Pamulang',
            'Pondok Aren',
            'Serpong',
            'Serpong Utara',
            'Setu',
        ];

        foreach ($kecamatan as $urutan => $nama) {
            Kecamatan::updateOrCreate(
                ['slug' => Str::slug($nama)],
                ['nama' => $nama, 'urutan' => $urutan + 1]
            );
        }
    }
}
