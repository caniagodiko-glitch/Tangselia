<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Nama tabel direktori yang menerima kolom sumber data eksternal.
     *
     * @var list<string>
     */
    private array $tabel = ['tempat', 'fasilitas_olahraga', 'layanan_kesehatan'];

    public function up(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->string('osm_id')->nullable()->unique()->after('slug');
                $table->string('google_place_id')->nullable()->unique()->after('osm_id');
                $table->enum('sumber', ['manual', 'osm'])->default('manual')->after('google_place_id');
                $table->string('jam_buka_teks')->nullable()->after('sumber');
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
                $table->dropUnique(['osm_id']);
                $table->dropUnique(['google_place_id']);
                $table->dropColumn(['osm_id', 'google_place_id', 'sumber', 'jam_buka_teks']);
            });
        }
    }
};
