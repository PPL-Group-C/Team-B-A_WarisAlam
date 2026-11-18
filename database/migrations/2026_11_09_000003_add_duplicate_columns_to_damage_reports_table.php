<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F08 Moderasi (PBI-24): tautan laporan duplikat ke laporan induknya.
 * rejected_as_duplicate menandai penolakan karena duplikat, supaya fitur
 * reputasi (F10) tidak mengurangi poin pelapornya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('damage_reports', function (Blueprint $t) {
            $t->foreignId('duplicate_of_id')->nullable()->after('moderator_note')
                ->constrained('damage_reports')->nullOnDelete();
            $t->boolean('rejected_as_duplicate')->default(false)->after('duplicate_of_id');
        });
    }

    public function down(): void
    {
        Schema::table('damage_reports', function (Blueprint $t) {
            $t->dropConstrainedForeignId('duplicate_of_id');
            $t->dropColumn('rejected_as_duplicate');
        });
    }
};
