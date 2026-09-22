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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tps_id')->constrained('tps');
            // Sengaja TIDAK ada voter_id di sini — anonimitas suara.
            // token_hash dipakai untuk mencegah token dipakai vote 2x (unique per position),
            // tapi tidak bisa ditelusuri balik ke voter tanpa tabel voter_tokens yg sudah nonaktif.
            $table->string('token_hash');
            $table->timestamp('waktu_vote');
            $table->boolean('synced')->default(false); // status sinkron dari Electron ke server pusat
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['position_id', 'token_hash']); // 1 token = 1 suara per posisi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
