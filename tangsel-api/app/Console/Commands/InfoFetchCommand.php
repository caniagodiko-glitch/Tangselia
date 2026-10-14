<?php

namespace App\Console\Commands;

use App\Services\InfoDirektoriFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('info:fetch {--fitur= : Batasi ke satu fitur: tempat, olahraga, kesehatan} {--maks=25 : Maksimal situs per fitur}')]
#[Description('Menggali info dari situs resmi bisnis (foto, deskripsi, menu) dengan menghormati robots.txt')]
class InfoFetchCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InfoDirektoriFetcher $fetcher): int
    {
        $fitur = $this->option('fitur');
        $maks = max(1, (int) $this->option('maks'));

        try {
            $stats = $fetcher->jalankan($fitur !== '' ? $fitur : null, $maks);
        } catch (Throwable $e) {
            $this->error('Penggalian info gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Situs diperiksa %d | dilewati %d | foto %d | deskripsi %d | menu %d | gagal %d',
            $stats['diperiksa'],
            $stats['dilewati'],
            $stats['foto'],
            $stats['deskripsi'],
            $stats['menu'],
            $stats['gagal'],
        ));

        return self::SUCCESS;
    }
}
