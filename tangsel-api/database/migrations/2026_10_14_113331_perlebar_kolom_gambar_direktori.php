<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perlebar kolom `gambar` agar dapat menampung URL foto (mis. dari Google
     * Maps) yang panjangnya melebihi batas varchar(255) bawaan.
     *
     * @var list<string>
     */
    private array $tabel = ['tempat', 'fasilitas_olahraga'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->string('gambar', 512)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->string('gambar', 255)->nullable()->change();
            });
        }
    }
};
