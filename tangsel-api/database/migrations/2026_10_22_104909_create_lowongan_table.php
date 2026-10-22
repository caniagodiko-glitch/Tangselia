<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the job posting table for the lowongan feature.
     */
    public function up(): void
    {
        Schema::create('lowongan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sumber_id')->nullable()->constrained('sumber')->nullOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori')->nullOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatan')->nullOnDelete();
            $table->string('kode')->unique();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('posisi')->nullable();
            $table->string('perusahaan')->nullable();
            $table->enum('jenis', ['magang', 'karir', 'part-time'])->default('karir');
            $table->text('deskripsi')->nullable();
            $table->text('kualifikasi')->nullable();
            $table->text('cara_apply')->nullable();
            $table->string('url_asli', 500)->nullable();
            $table->string('sumber')->nullable();
            $table->string('sumber_url', 500)->nullable();
            $table->enum('status', ['baru', 'tayang', 'ditolak', 'kedaluwarsa'])->default('baru');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('tayang_at')->nullable();
            $table->timestamp('kedaluwarsa_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['jenis', 'status']);
            $table->index('kecamatan_id');
        });
    }

    /**
     * Drop the job posting table.
     */
    public function down(): void
    {
        Schema::dropIfExists('lowongan');
    }
};
