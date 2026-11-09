<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F08 Moderasi (PBI-22): riwayat setiap perubahan status laporan.
 * Dibaca juga oleh timeline pelapor (Mumtaz) dan notifikasi (Fasya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_status_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('damage_reports')->cascadeOnDelete();
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20)->index();
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('note')->nullable();
            $t->timestamps();

            $t->index(['report_id', 'created_at']);
        });

        Schema::table('damage_reports', function (Blueprint $t) {
            $t->foreignId('moderated_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $t->timestamp('moderated_at')->nullable()->after('moderated_by');
        });
    }

    public function down(): void
    {
        Schema::table('damage_reports', function (Blueprint $t) {
            $t->dropConstrainedForeignId('moderated_by');
            $t->dropColumn('moderated_at');
        });

        Schema::dropIfExists('report_status_histories');
    }
};
