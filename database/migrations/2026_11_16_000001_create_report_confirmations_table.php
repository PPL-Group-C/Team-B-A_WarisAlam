<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F08 Moderasi (PBI-30): konfirmasi / bantahan warga atas sebuah laporan.
 * Satu warga hanya punya satu suara per laporan (bisa diganti confirm ↔ dispute).
 * Tombol konfirmasi/bantah untuk warga dibuat di PBI-28 (Miftahul) memakai tabel ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_confirmations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('damage_reports')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('type', 10); // confirm | dispute
            $t->timestamps();

            $t->unique(['report_id', 'user_id']);
            $t->index(['report_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_confirmations');
    }
};
