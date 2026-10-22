<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tandai lowongan yang judulnya memang menyebut lowongan kerja.
     *
     * News aggregator menyertakan berita umum yang kebetulan memuat kata
     * "loker". Penanda ini membantu administrator memfilter kandidat
     * sebelum kurasi tanpa membuang lowongan yang sah.
     */
    public function up(): void
    {
        Schema::table('lowongan', function (Blueprint $table) {
            $table->boolean('relevan')->default(true)->after('status');
            $table->index(['relevan', 'status']);
        });
    }

    /**
     * Hapus penanda relevansi.
     */
    public function down(): void
    {
        Schema::table('lowongan', function (Blueprint $table) {
            $table->dropIndex(['relevan', 'status']);
            $table->dropColumn('relevan');
        });
    }
};
