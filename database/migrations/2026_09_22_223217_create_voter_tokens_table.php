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
        Schema::create('voter_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tps_id')->constrained('tps')->cascadeOnDelete();
            $table->string('token_hash')->unique(); // hash dari kode QR acak
            $table->enum('status', ['aktif', 'terpakai', 'kedaluwarsa'])->default('aktif');
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamp('kedaluwarsa_pada');
            $table->timestamp('dipakai_pada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voter_tokens');
    }
};
