<?php

namespace App\Console\Commands;

use App\Services\WikipediaFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('wiki:sync')]
#[Description('Melengkapi data rumah sakit dari Wikipedia bahasa Indonesia (CC BY-SA 4.0)')]
class WikiSyncCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WikipediaFetcher $fetcher): int
    {
        try {
            $stats = $fetcher->sinkronkanRumahSakit();
        } catch (Throwable $e) {
            $this->error('Sinkronisasi Wikipedia gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Baris dibaca %d | dicocokkan %d | alamat terisi %d | deskripsi terisi %d',
            $stats['baris_dibaca'],
            $stats['dicocokkan'],
            $stats['alamat_terisi'],
            $stats['deskripsi_terisi'],
        ));

        return self::SUCCESS;
    }
}
