<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBI-14 menampilkan jumlah pengadopsi situs. Tombol adopsi / berhenti
 * adopsi menyusul di Sprint 2 (PBI-37, Raihan) memakai tabel yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_adoptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('heritage_site_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();

            $t->unique(['heritage_site_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_adoptions');
    }
};
