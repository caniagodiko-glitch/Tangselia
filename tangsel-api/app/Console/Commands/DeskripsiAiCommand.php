<?php

namespace App\Console\Commands;

use App\Services\AIDeskripsiTempat;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

#[Signature('deskripsi:ai {--fitur= : Batasi ke satu fitur: tempat, olahraga, kesehatan} {--maks=50 : Maksimal entri per fitur}')]
#[Description('Merangkai deskripsi kaya dari data nyata memakai Gemini (paket Gratis, tanpa kartu)')]
class DeskripsiAiCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AIDeskripsiTempat $ai): int
    {
        if (! is_string(config('ai.key')) || config('ai.key') === '') {
            $this->warn('GEMINI_API_KEY belum diisi — deskripsi tetap memakai template OSM/situs/Wikipedia.');
            $this->line('Dapatkan kunci gratis di https://aistudio.google.com/apikey lalu isi ke .env.');

            return self::SUCCESS;
        }

        $filter = $this->option('fitur');
        $maks = max(1, (int) $this->option('maks'));
        $total = 0;

        foreach ((array) config('osm.fitur') as $kunci => $definisi) {
            if ($filter !== null && $filter !== '' && $kunci !== $filter) {
                continue;
            }

            $modelClass = $definisi['model'];

            $modelClass::with('kecamatan:id,nama')
                ->where(function ($query) {
                    $query->whereNull('deskripsi_sumber')->orWhere('deskripsi_sumber', 'osm');
                })
                ->orderBy('id')
                ->limit($maks)
                ->get()
                ->each(function (Model $tempat) use ($ai, $definisi, &$total): void {
                    $teks = $ai->rangkai($tempat, [
                        'jenis' => str_replace('_', ' ', (string) $tempat->jenis).' ('.$definisi['label'].')',
                        'kecamatan' => $tempat->kecamatan?->nama ?? '-',
                        'fasilitas' => is_array($tempat->fasilitas) && $tempat->fasilitas !== []
                            ? implode(', ', $tempat->fasilitas)
                            : '-',
                    ]);

                    if ($teks === null) {
                        return;
                    }

                    $tempat->deskripsi = $teks;
                    $tempat->deskripsi_sumber = 'ai';
                    $tempat->atribusi = $this->ejaAtribusi($tempat->atribusi, [
                        'jenis' => 'deskripsi',
                        'sumber' => 'Gemini AI',
                        'tautan' => 'https://ai.google.dev',
                        'peran' => 'deskripsi dirangkai dari data OpenStreetMap yang tersedia',
                        'catatan' => 'Tidak menambah fakta baru; alamat, jam, dan fasilitas tetap dari sumber asli.',
                    ]);
                    $tempat->save();

                    $total++;

                    usleep((int) config('ai.jeda_ms', 1500) * 1000);
                });
        }

        $this->info("Deskripsi AI dibuat untuk {$total} entri.");

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>|null  $atribusi
     * @return list<array<string, mixed>>
     */
    private function ejaAtribusi(?array $atribusi, array $baru): array
    {
        if ($atribusi === null || $atribusi === []) {
            $atribusi = [];
        }

        foreach ($atribusi as $entri) {
            if (($entri['jenis'] ?? null) === $baru['jenis'] && ($entri['sumber'] ?? null) === $baru['sumber']) {
                return $atribusi;
            }
        }

        return [...$atribusi, $baru];
    }
}
