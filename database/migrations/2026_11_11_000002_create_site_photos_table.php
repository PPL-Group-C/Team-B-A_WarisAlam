<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBI-13: galeri foto situs. Diisi lewat upload admin (Ganang, PBI-08);
 * path disimpan relatif terhadap disk "public" (atau URL penuh).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('heritage_site_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('caption')->nullable();
            $t->string('credit')->nullable(); // sumber / lisensi foto
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();

            $t->index(['heritage_site_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_photos');
    }
};
