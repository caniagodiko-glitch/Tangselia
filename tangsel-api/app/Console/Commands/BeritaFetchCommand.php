<?php

namespace App\Console\Commands;

use App\Services\NewsFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('berita:fetch {--source= : Nama atau id sumber tertentu saja}')]
#[Description('Mengambil berita dari seluruh sumber RSS aktif lalu meringkasnya')]
class BeritaFetchCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(NewsFetcher $fetcher): int
    {
        $stats = $fetcher->run($this->option('source'));

        $this->info(sprintf(
            'Sumber: %d | Berita baru: %d | Lewat (duplikat/kosong): %d | Sumber gagal: %d',
            $stats['sumber'],
            $stats['dibuat'],
            $stats['dilewati'],
            $stats['gagal'],
        ));

        return self::SUCCESS;
    }
}
