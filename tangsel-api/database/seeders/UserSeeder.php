<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tangselia.id'],
            [
                'nama' => 'Administrator',
                'password' => 'password',
                'role' => 'admin',
                'telepon' => '081200000001',
                'aktif' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'warga@tangselia.id'],
            [
                'nama' => 'Warga Contoh',
                'password' => 'password',
                'role' => 'warga',
                'telepon' => '081200000002',
                'aktif' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
