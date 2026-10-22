<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the citizen report tables replaced by the lowongan feature.
     */
    public function up(): void
    {
        Schema::dropIfExists('laporan_foto');
        Schema::dropIfExists('laporan');
    }

    /**
     * Recreate the citizen report tables.
     */
    public function down(): void
    {
        Schema::create('laporan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->foreignId('pelapor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatan')->nullOnDelete();
            $table->string('judul');
            $table->text('deskripsi');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->enum('status', ['baru', 'diverifikasi', 'ditolak', 'diproses', 'selesai'])->default('baru');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('diselesaikan_at')->nullable();
            $table->timestamps();
        });

        Schema::create('laporan_foto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan')->cascadeOnDelete();
            $table->string('gambar');
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();
        });
    }
};
