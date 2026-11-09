<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F08 Moderasi (PBI-23): hasil keputusan moderator.
 * - rejection_reason: dibaca pelapor (fitur Mumtaz).
 * - moderator_note: catatan kurasi untuk instansi.
 * - severity_original: severity asli pelapor sebelum disesuaikan moderator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('damage_reports', function (Blueprint $t) {
            $t->unsignedTinyInteger('severity_original')->nullable()->after('severity');
            $t->text('rejection_reason')->nullable()->after('moderated_at');
            $t->text('moderator_note')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('damage_reports', function (Blueprint $t) {
            $t->dropColumn(['severity_original', 'rejection_reason', 'moderator_note']);
        });
    }
};
