<?php

namespace App\Console\Commands;

use App\Models\Lowongan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lowongan:publish {--source= : Nama atau id sumber tertentu saja} {--limit=0 : Batas jumlah yang ditayangkan, 0 berarti semua}')]
#[Description('Menayangkan lowongan berstatus baru yang relevan dan punya URL asli langsung')]
class LowonganPublishCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = Lowongan::query()
            ->where('status', 'baru')
            ->where('relevan', true)
            ->whereNotNull('url_asli')
            ->where('url_asli', 'not like', '%news.google.com%')
            ->where('url_asli', 'not like', '%bing.com/news/apiclick%')
            ->orderBy('id');

        $sumber = $this->option('source');

        if (is_string($sumber) && $sumber !== '') {
            $query->where(function ($inner) use ($sumber) {
                $inner->where('sumber_id', $sumber)
                    ->orWhereHas('sumberRelasi', fn ($relasi) => $relasi->where('nama', 'like', '%'.$sumber.'%'));
            });
        }

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $jumlah = 0;

        foreach ($query->get() as $lowongan) {
            $sekarang = now();
            $kedaluwarsa = $lowongan->kedaluwarsa_at;

            if ($kedaluwarsa === null || $kedaluwarsa->lte($sekarang)) {
                $kedaluwarsa = $sekarang->copy()->addDays((int) config('lowongan.kedaluwarsa_hari', 30));
            }

            $lowongan->update([
                'status' => 'tayang',
                'tayang_at' => $sekarang,
                'published_at' => $lowongan->published_at ?? $sekarang,
                'kedaluwarsa_at' => $kedaluwarsa,
            ]);

            $jumlah++;
        }

        $this->info(sprintf('Lowongan ditayangkan: %d', $jumlah));

        return self::SUCCESS;
    }
}
