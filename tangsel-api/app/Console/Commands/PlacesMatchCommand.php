<?php

namespace App\Console\Commands;

use App\Services\GooglePlacesLookup;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('places:match {--fitur= : Batasi ke satu fitur: tempat, olahraga, kesehatan}')]
#[Description('Mencocokkan tempat direktori dengan place_id Google Places (hanya place_id yang disimpan)')]
class PlacesMatchCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GooglePlacesLookup $lookup): int
    {
        if (! $lookup->tersedia()) {
            $this->warn('GOOGLE_MAPS_API_KEY belum diisi. Pencocokan dilewati.');

            return self::SUCCESS;
        }

        $filter = $this->option('fitur');

        foreach ((array) config('osm.fitur') as $kunci => $definisi) {
            if ($filter !== null && $filter !== '' && $kunci !== $filter) {
                continue;
            }

            $modelClass = $definisi['model'];
            $kandidat = 0;
            $cocok = 0;

            $modelClass::query()
                ->where('status', 'aktif')
                ->whereNull('google_place_id')
                ->chunkById(100, function ($rows) use ($lookup, &$kandidat, &$cocok) {
                    foreach ($rows as $row) {
                        $kandidat++;
                        $placeId = $lookup->cari($row->nama, (float) $row->latitude, (float) $row->longitude);

                        if ($placeId !== null) {
                            $row->update(['google_place_id' => $placeId]);
                            $cocok++;
                        }
                    }
                });

            $this->info("{$definisi['label']}: {$cocok} place_id cocok dari {$kandidat} kandidat.");
        }

        return self::SUCCESS;
    }
}
