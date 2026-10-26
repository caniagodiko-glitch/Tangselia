<?php

namespace Database\Seeders;

use App\Models\FasilitasOlahraga;
use App\Models\LayananKesehatan;
use App\Models\Tempat;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UlasanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Contoh ulasan warga untuk keperluan demonstrasi modul Jelajah.
     * Entri dipilih acak dari direktori yang sudah ada sehingga seeder
     * tidak bergantung pada slug tertentu hasil impor OpenStreetMap.
     */
    public function run(): void
    {
        $warga = User::where('email', 'warga@tangselia.id')->first();

        if (! $warga) {
            return;
        }

        $contoh = [
            [
                'kelas' => Tempat::class,
                'rating' => 5,
                'komentar' => 'Tempatnya bersih, pelayanannya cepat, dan harganya masih masuk akal.',
            ],
            [
                'kelas' => Tempat::class,
                'rating' => 4,
                'komentar' => 'Rasa dan suasananya oke. Parkir agak sempit kalau sedang ramai.',
            ],
            [
                'kelas' => Tempat::class,
                'rating' => 5,
                'komentar' => 'Cocok untuk mampir sore hari. Stafnya ramah dan responsif.',
            ],
            [
                'kelas' => FasilitasOlahraga::class,
                'rating' => 4,
                'komentar' => 'Lapangannya terawat dan pencahayaan bagus untuk bermain malam.',
            ],
            [
                'kelas' => LayananKesehatan::class,
                'rating' => 5,
                'komentar' => 'Pendaftaran cepat dan ruang tunggunya nyaman.',
            ],
        ];

        foreach ($contoh as $item) {
            $kelas = $item['kelas'];

            $entitas = $kelas::query()
                ->where('status', 'aktif')
                ->inRandomOrder()
                ->first();

            if (! $entitas) {
                continue;
            }

            Ulasan::firstOrCreate(
                [
                    'direktori_type' => $kelas,
                    'direktori_id' => $entitas->id,
                    'user_id' => $warga->id,
                ],
                [
                    'rating' => $item['rating'],
                    'komentar' => $item['komentar'],
                    'foto' => null,
                    'status' => 'terbit',
                ]
            );
        }
    }
}
