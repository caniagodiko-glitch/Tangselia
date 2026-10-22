<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan sidik jari judul agar lowongan yang sama dari beberapa
     * sumber tidak tercatat dua kali.
     *
     * Satu artikel lowonganongan dapat muncul di beberapa query, bahkan dari
     * agregator berbeda. Pencocokan judul yang dinormalisasi membuat
     * tabel lowongan tetap bersih.
     */
    public function up(): void
    {
        Schema::table('lowongan', function (Blueprint $table) {
            $table->string('judul_hash', 40)->nullable()->after('slug');
            $table->index('judul_hash');
        });
    }

    /**
     * Hapus sidik jari judul.
     */
    public function down(): void
    {
        Schema::table('lowongan', function (Blueprint $table) {
            $table->dropIndex(['judul_hash']);
            $table->dropColumn('judul_hash');
        });
    }
};
