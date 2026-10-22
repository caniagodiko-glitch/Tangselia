<?php

namespace App\Console\Commands;

use App\Models\Lowongan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lowongan:expire')]
#[Description('Menandai lowongan yang masa tayangnya habis sebagai kedaluwarsa')]
class LowonganExpireCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $jumlah = Lowongan::where('status', 'tayang')
            ->whereNotNull('kedaluwarsa_at')
            ->where('kedaluwarsa_at', '<=', now())
            ->update(['status' => 'kedaluwarsa']);

        $this->info(sprintf('Lowongan kedaluwarsa: %d', $jumlah));

        return self::SUCCESS;
    }
}
