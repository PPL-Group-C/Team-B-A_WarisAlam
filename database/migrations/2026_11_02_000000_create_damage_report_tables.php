<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heritage_sites', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('registration_id')->unique();
            $t->string('heritage_note')->nullable();
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->timestamps();
        });

        Schema::create('damage_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('heritage_site_id')->constrained()->restrictOnDelete();
            $t->string('status', 20)->default('draft')->index(); // draft | submitted | ...
            $t->string('category', 30)->nullable();
            $t->unsignedTinyInteger('severity')->nullable();      // 1..5
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->unsignedSmallInteger('gps_accuracy_m')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_anonymous')->default(false);
            $t->timestamp('declaration_accepted_at')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
        });

        Schema::create('report_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('damage_report_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('original_name');
            $t->unsignedInteger('size_kb');
            $t->boolean('exif_valid')->default(false);
            $t->decimal('exif_latitude', 10, 7)->nullable();
            $t->decimal('exif_longitude', 10, 7)->nullable();
            $t->timestamp('taken_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_photos');
        Schema::dropIfExists('damage_reports');
        Schema::dropIfExists('heritage_sites');
    }
};
