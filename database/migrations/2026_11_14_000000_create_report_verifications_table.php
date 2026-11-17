<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_verifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('damage_report_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('verdict', 10);          // confirm | dispute
            $t->string('comment', 500)->nullable();
            $t->timestamps();

            $t->unique(['damage_report_id', 'user_id']);   // one vote per user per report
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_verifications');
    }
};
