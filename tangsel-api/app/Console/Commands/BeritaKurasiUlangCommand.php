<?php

namespace App\Console\Commands;

use App\Models\Berita;
use App\Services\BeritaWilayah;
use App\Services\Categorizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('berita:kurasi-ulang {--kecamatan : Hanya perbarui kecamatan, status tidak diubah} {--dry-run : Tampilkan ringkasan tanpa menyimpan perubahan}')]
#[Description('Menyaring ulang berita agregat dan melengkapi kecamatan')]
class BeritaKurasiUlangCommand extends Command
{
    private const STATUS = [
        BeritaWilayah::TERIMA => 'published',
        BeritaWilayah::DRAFT => 'draft',
        BeritaWilayah::TOLAK => 'archived',
    ];

    /**
     * Execute the console command.
     */
    public function handle(BeritaWilayah $wilayah, Categorizer $categorizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $hanyaKecamatan = (bool) $this->option('kecamatan');

        $hitung = [
            'total' => 0,
            BeritaWilayah::TERIMA => 0,
            BeritaWilayah::DRAFT => 0,
            BeritaWilayah::TOLAK => 0,
            'berubah_status' => 0,
            'berubah_kecamatan' => 0,
        ];

        Berita::query()
            ->where('tipe', 'agregat')
            ->chunkById(100, function (Collection $daftar) use ($wilayah, $categorizer, $hanyaKecamatan, $dryRun, &$hitung): void {
                foreach ($daftar as $berita) {
                    $hitung['total']++;

                    $keputusan = $wilayah->keputusan($berita->judul, $berita->ringkasan, $berita->sumber);
                    $hitung[$keputusan]++;

                    $klasifikasi = $categorizer->categorize($berita->judul, $berita->ringkasan);
                    $perubahan = [];

                    $statusBaru = $hanyaKecamatan ? null : self::STATUS[$keputusan];

                    if ($statusBaru !== null && $berita->status !== $statusBaru) {
                        $perubahan['status'] = $statusBaru;
                    }

                    if ($berita->kecamatan_id !== $klasifikasi['kecamatan_id']) {
                        $perubahan['kecamatan_id'] = $klasifikasi['kecamatan_id'];
                    }

                    if ($perubahan === []) {
                        continue;
                    }

                    if (! $dryRun) {
                        $berita->update($perubahan);
                    }

                    if (isset($perubahan['status'])) {
                        $hitung['berubah_status']++;
                    }

                    if (array_key_exists('kecamatan_id', $perubahan)) {
                        $hitung['berubah_kecamatan']++;
                    }
                }
            });

        $this->newLine();
        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Berita agregat diperiksa', $hitung['total']],
                ['Layak tayang', $hitung[BeritaWilayah::TERIMA]],
                ['Disimpan sebagai draft', $hitung[BeritaWilayah::DRAFT]],
                ['Diarsipkan', $hitung[BeritaWilayah::TOLAK]],
                ['Status berubah', $hitung['berubah_status']],
                ['Kecamatan berubah', $hitung['berubah_kecamatan']],
            ],
        );

        if ($dryRun) {
            $this->warn('Mode uji: tidak ada data yang diubah.');
        } else {
            $this->info('Selesai. Data berita sudah diperbarui.');
        }

        return self::SUCCESS;
    }
}
