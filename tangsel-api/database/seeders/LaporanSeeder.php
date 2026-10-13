<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Kecamatan;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Database\Seeder;

class LaporanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Empat laporan mewakili status baru, diverifikasi, diproses, dan selesai.
     */
    public function run(): void
    {
        $warga = User::where('email', 'warga@tangselia.id')->first();
        $kategoriJalan = Kategori::where('slug', 'jalan-rusak-atau-infrastruktur')->first();
        $kategoriSampah = Kategori::where('slug', 'sampah-dan-kebersihan')->first();
        $kategoriFasilitas = Kategori::where('slug', 'fasilitas-umum')->first();

        $laporan = [
            [
                'kode' => 'LPR-2610-00001',
                'kategori_id' => $kategoriJalan->id,
                'kecamatan' => 'pamulang',
                'judul' => 'Jalan berlubang di Jl. Siliwangi',
                'deskripsi' => 'Lubang sebesar meteran di lajur kanan depan pasar, berbahaya untuk pengendara motor.',
                'latitude' => -6.2790000,
                'longitude' => 106.7400000,
                'status' => 'diverifikasi',
                'catatan_admin' => 'Laporan diverifikasi, akan diteruskan ke bidang terkait.',
                'diselesaikan_at' => null,
            ],
            [
                'kode' => 'LPR-2610-00002',
                'kategori_id' => $kategoriSampah->id,
                'kecamatan' => 'pondok-aren',
                'judul' => 'Tumpukan sampah di pinggir jalan',
                'deskripsi' => 'Sampah menumpuk tiga hari di depan gang, bau menyengat warga sekitar.',
                'latitude' => -6.2760000,
                'longitude' => 106.7210000,
                'status' => 'diproses',
                'catatan_admin' => 'Sudah dihubungi petugas kebersihan, dijadwalkan angkut besok pagi.',
                'diselesaikan_at' => null,
            ],
            [
                'kode' => 'LPR-2610-00003',
                'kategori_id' => $kategoriFasilitas->id,
                'kecamatan' => 'serpong',
                'judul' => 'Lampu jalan mati di Jl. Raya Serpong',
                'deskripsi' => 'Tiga titik lampu jalan padam sejak seminggu lalu, jalanan gelap pada malam hari.',
                'latitude' => -6.3170000,
                'longitude' => 106.7210000,
                'status' => 'baru',
                'catatan_admin' => null,
                'diselesaikan_at' => null,
            ],
            [
                'kode' => 'LPR-2610-00004',
                'kategori_id' => $kategoriFasilitas->id,
                'kecamatan' => 'ciputat',
                'judul' => 'Pagar halaman sekolah roboh',
                'deskripsi' => 'Pagar sisi timur roboh setelah hujan deras, menghalangi trotoar.',
                'latitude' => -6.3180000,
                'longitude' => 106.6980000,
                'status' => 'selesai',
                'catatan_admin' => 'Pagar sudah diperbaiki oleh petugas kebersihan dan prasarana.',
                'diselesaikan_at' => now()->subDay(),
            ],
        ];

        foreach ($laporan as $item) {
            Laporan::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'kategori_id' => $item['kategori_id'],
                    'pelapor_id' => $warga->id,
                    'kecamatan_id' => Kecamatan::where('slug', $item['kecamatan'])->first()->id,
                    'judul' => $item['judul'],
                    'deskripsi' => $item['deskripsi'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'status' => $item['status'],
                    'catatan_admin' => $item['catatan_admin'],
                    'diselesaikan_at' => $item['diselesaikan_at'],
                ]
            );
        }
    }
}
