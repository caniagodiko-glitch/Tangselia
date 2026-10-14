<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel direktori yang menerima kolom pengayaan data (Opsi B).
     *
     * @var list<string>
     */
    private array $tabel = ['tempat', 'fasilitas_olahraga', 'layanan_kesehatan'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tabel as $nama) {
            if (! Schema::hasColumn($nama, 'website')) {
                Schema::table($nama, function (Blueprint $table) {
                    $table->string('website')->nullable()->after('google_place_id');
                });
            }

            if ($nama !== 'layanan_kesehatan' && ! Schema::hasColumn($nama, 'telepon')) {
                Schema::table($nama, function (Blueprint $table) {
                    $table->string('telepon', 20)->nullable()->after('website');
                });
            }

            Schema::table($nama, function (Blueprint $table) {
                $table->string('deskripsi_sumber', 20)->default('osm')->after('deskripsi');
                $table->json('atribusi')->nullable()->after('status');
                $table->timestamp('info_diunduh_pada')->nullable()->after('atribusi');
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
                $table->dropColumn(['website', 'deskripsi_sumber', 'atribusi', 'info_diunduh_pada']);
            });

            if ($nama !== 'layanan_kesehatan' && Schema::hasColumn($nama, 'telepon')) {
                Schema::table($nama, function (Blueprint $table) {
                    $table->dropColumn(['telepon']);
                });
            }
        }
    }
};
