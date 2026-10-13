<?php

namespace App\Console\Commands;

use App\Services\OsmImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('places:sync {--fitur= : Batasi ke satu fitur: tempat, olahraga, kesehatan}')]
#[Description('Menyinkronkan direktori tempat dari OpenStreetMap (entri lama tidak ditimpa)')]
class PlacesSyncCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OsmImporter $importer): int
    {
        $filter = $this->option('fitur');

        foreach ((array) config('osm.fitur') as $kunci => $definisi) {
            if ($filter !== null && $filter !== '' && $kunci !== $filter) {
                continue;
            }

            try {
                $stats = $importer->import($kunci);
            } catch (Throwable $e) {
                $this->error("{$definisi['label']} gagal: {$e->getMessage()}");

                continue;
            }

            $this->info(sprintf(
                '%s: dibuat %d | dilewati %d | gagal %d',
                $definisi['label'],
                $stats['dibuat'],
                $stats['dilewati'],
                $stats['gagal'],
            ));
        }

        return self::SUCCESS;
    }
}
