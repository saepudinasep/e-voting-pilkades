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
        Schema::create('audit_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vote_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_struk')->unique();
            $table->string('qr_verifikasi'); // kode QR di struk fisik, untuk cek independen
            $table->timestamp('dicetak_pada');
            $table->enum('status_audit', ['belum_dicek', 'cocok', 'selisih'])->default('belum_dicek');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_receipts');
    }
};
