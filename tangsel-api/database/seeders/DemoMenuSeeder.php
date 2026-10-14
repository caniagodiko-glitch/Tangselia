<?php

namespace Database\Seeders;

use App\Models\Tempat;
use Illuminate\Database\Seeder;

/**
 * DemoMenuSeeder
 * ==============
 *
 * Mengisi kolom `menu` untuk tempat kuliner yang belum punya menu, agar
 * halaman detail terlihat lengkap saat presentasi/demo.
 *
 * PENTING: menu di sini adalah CONTOH (ilustrasi), bukan hasil survei. Setiap
 * tempat yang diisi diberi entri atribusi "Contoh (demo)" supaya jelas
 * mana data asli dan mana data ilustrasi. Seeder ini idempoten: hanya mengisi
 * tempat yang `menu`-nya masih kosong.
 *
 * Jalankan: php artisan db:seed --class=DemoMenuSeeder
 */
class DemoMenuSeeder extends Seeder
{
    /**
     * Menu contoh per jenis tempat.
     *
     * @var array<string, list<list<array<string, string>>>>
     */
    private array $menuPerJenis = [
        'kafe' => [
            [
                ['nama' => 'Es Kopi Susu Gula Aren', 'harga' => 'Rp20.000'],
                ['nama' => 'Americano', 'harga' => 'Rp22.000'],
                ['nama' => 'Cafe Latte', 'harga' => 'Rp28.000'],
                ['nama' => 'Matcha Latte', 'harga' => 'Rp30.000'],
                ['nama' => 'Croissant Butter', 'harga' => 'Rp25.000'],
                ['nama' => 'French Fries', 'harga' => 'Rp22.000'],
            ],
            [
                ['nama' => 'Kopi Susu Spesial', 'harga' => 'Rp22.000'],
                ['nama' => 'Cappuccino', 'harga' => 'Rp27.000'],
                ['nama' => 'V60 Single Origin', 'harga' => 'Rp30.000'],
                ['nama' => 'Chocolate Ice', 'harga' => 'Rp26.000'],
                ['nama' => 'Roti Bakar Keju', 'harga' => 'Rp20.000'],
                ['nama' => 'Pisang Goreng', 'harga' => 'Rp18.000'],
            ],
        ],
        'restoran' => [
            [
                ['nama' => 'Nasi Goreng Spesial', 'harga' => 'Rp25.000'],
                ['nama' => 'Ayam Geprek + Nasi', 'harga' => 'Rp23.000'],
                ['nama' => 'Mie Goreng Jawa', 'harga' => 'Rp22.000'],
                ['nama' => 'Soto Ayam', 'harga' => 'Rp20.000'],
                ['nama' => 'Nasi Putih', 'harga' => 'Rp5.000'],
                ['nama' => 'Es Teh Manis', 'harga' => 'Rp7.000'],
            ],
            [
                ['nama' => 'Nasi Ayam Bakar', 'harga' => 'Rp28.000'],
                ['nama' => 'Ikan Lele Goreng + Sambal', 'harga' => 'Rp25.000'],
                ['nama' => 'Bakso Sapi', 'harga' => 'Rp20.000'],
                ['nama' => 'Capcay Goreng', 'harga' => 'Rp22.000'],
                ['nama' => 'Kerupuk', 'harga' => 'Rp3.000'],
                ['nama' => 'Es Jeruk', 'harga' => 'Rp8.000'],
            ],
        ],
        'warkop' => [
            [
                ['nama' => 'Kopi Hitam', 'harga' => 'Rp6.000'],
                ['nama' => 'Kopi Susu', 'harga' => 'Rp10.000'],
                ['nama' => 'Indomie Goreng Telur', 'harga' => 'Rp13.000'],
                ['nama' => 'Nasi Goreng Warkop', 'harga' => 'Rp15.000'],
                ['nama' => 'Gorengan (per pcs)', 'harga' => 'Rp2.000'],
                ['nama' => 'Teh Manis Panas', 'harga' => 'Rp5.000'],
            ],
        ],
    ];

    public function run(): void
    {
        $jumlah = 0;

        Tempat::query()
            ->whereNull('menu')
            ->orderBy('id')
            ->chunkById(100, function ($daftar) use (&$jumlah): void {
                foreach ($daftar as $tempat) {
                    $pilihan = $this->menuPerJenis[$tempat->jenis] ?? null;

                    if ($pilihan === null) {
                        continue;
                    }

                    $menu = $pilihan[$tempat->id % count($pilihan)];

                    $atribusi = is_array($tempat->atribusi) ? $tempat->atribusi : [];
                    $atribusi[] = [
                        'jenis' => 'contoh',
                        'sumber' => 'Contoh (demo)',
                        'peran' => 'menu ilustrasi untuk keperluan demo',
                    ];

                    $tempat->menu = $menu;
                    $tempat->atribusi = $atribusi;
                    $tempat->save();

                    $jumlah++;
                }
            });

        $this->command->info("DemoMenuSeeder: {$jumlah} tempat diisi menu contoh.");
    }
}
