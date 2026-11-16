<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fondasi + Detail Situs (Raihan, PBI-13/14): kolom detail pada tabel
 * heritage_sites yang dibuat F06 (Miftahul, 2026_11_02_000000).
 *
 * Dipakai juga oleh peta (Fasya: type, category, province, city, status,
 * threat_score) dan CRUD situs (Ganang). Koordinat tetap memakai kolom
 * latitude/longitude yang sudah ada.
 *
 * status & threat_score diisi scheduler skor ancaman di Sprint 2 (FR-23/24);
 * situs baru mulai dari "safe" (FR-07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heritage_sites', function (Blueprint $t) {
            $t->string('type', 20)->default('cultural')->after('name')->index(); // App\Enums\SiteType
            $t->string('category', 100)->nullable()->after('type');               // mis. "UNESCO World Heritage"
            $t->string('province', 100)->nullable()->after('category')->index();
            $t->string('city', 100)->nullable()->after('province');
            $t->text('description')->nullable()->after('longitude');              // ringkasan singkat
            $t->text('history')->nullable()->after('description');                // PBI-13: sejarah
            $t->json('facts')->nullable()->after('history');                      // PBI-13: [{"label": "...", "value": "..."}]
            $t->string('status', 20)->default('safe')->after('facts')->index();   // App\Enums\SiteStatus
            $t->decimal('threat_score', 8, 2)->default(0)->after('status');       // PBI-14
            $t->timestamp('score_updated_at')->nullable()->after('threat_score');
        });
    }

    public function down(): void
    {
        Schema::table('heritage_sites', function (Blueprint $t) {
            $t->dropIndex(['type']);
            $t->dropIndex(['province']);
            $t->dropIndex(['status']);
        });

        Schema::table('heritage_sites', function (Blueprint $t) {
            $t->dropColumn([
                'type', 'category', 'province', 'city', 'description', 'history',
                'facts', 'status', 'threat_score', 'score_updated_at',
            ]);
        });
    }
};
