<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tps_id')->nullable()->constrained('tps')->nullOnDelete();
            $table->string('nik_hash')->unique(); // hash NIK untuk lookup tanpa simpan NIK asli di kolom ini
            $table->text('nik_encrypted'); // NIK asli, dienkripsi (cast 'encrypted' di model)
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->enum('status_verifikasi', ['belum', 'terverifikasi', 'ditolak'])->default('belum');
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
