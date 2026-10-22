<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Swap the report category scope for the job posting scope.
     */
    public function up(): void
    {
        DB::table('kategori')->where('scope', 'laporan')->delete();

        Schema::table('kategori', function (Blueprint $table) {
            $table->enum('scope', ['berita', 'lowongan'])->change();
        });
    }

    /**
     * Restore the report category scope.
     */
    public function down(): void
    {
        Schema::table('kategori', function (Blueprint $table) {
            $table->enum('scope', ['berita', 'laporan'])->change();
        });
    }
};
