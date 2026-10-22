<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the source table so it can hold both news and job sources.
     */
    public function up(): void
    {
        Schema::rename('berita_sumber', 'sumber');

        Schema::table('sumber', function (Blueprint $table) {
            $table->string('tipe', 20)->default('berita')->after('nama');
            $table->index(['tipe', 'aktif']);
        });
    }

    /**
     * Restore the original table name and drop the discriminator column.
     */
    public function down(): void
    {
        Schema::table('sumber', function (Blueprint $table) {
            $table->dropIndex(['tipe', 'aktif']);
            $table->dropColumn('tipe');
        });

        Schema::rename('sumber', 'berita_sumber');
    }
};
