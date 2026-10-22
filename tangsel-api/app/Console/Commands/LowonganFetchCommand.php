<?php

namespace App\Console\Commands;

use App\Services\LowonganFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lowongan:fetch {--source= : Nama atau id sumber tertentu saja}')]
#[Description('Mengambil lowongan kerja dari sumber RSS aktif lalu mengekstrak datanya')]
class LowonganFetchCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(LowonganFetcher $fetcher): int
    {
        $stats = $fetcher->run($this->option('source'));

        $this->info(sprintf(
            'Sumber: %d | Lowongan baru: %d | Lewat (duplikat/kosong): %d | Sumber gagal: %d',
            $stats['sumber'],
            $stats['dibuat'],
            $stats['dilewati'],
            $stats['gagal'],
        ));

        return self::SUCCESS;
    }
}
