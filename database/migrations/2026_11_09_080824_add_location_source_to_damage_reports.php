<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('damage_reports', function (Blueprint $t) {
            $t->string('location_source', 10)->nullable()->after('gps_accuracy_m');
        });
    }

    public function down(): void
    {
        Schema::table('damage_reports', function (Blueprint $t) {
            $t->dropColumn('location_source');
        });
    }
};