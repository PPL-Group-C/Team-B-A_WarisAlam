<?php

/*
| F08 Moderasi Laporan (Akmal). Dimuat dengan middleware "web" dari
| bootstrap/app.php supaya tidak bentrok dengan routes/web.php.
*/

use App\Http\Controllers\ModerationActionController;
use App\Http\Controllers\ModerationController;
use Illuminate\Support\Facades\Route;

Route::middleware('can:moderate-reports')
    ->prefix('moderasi')
    ->name('moderation.')
    ->group(function () {
        Route::get('/', [ModerationController::class, 'index'])->name('index');

        Route::post('/{report}/setujui', [ModerationActionController::class, 'approve'])->name('approve');
        Route::post('/{report}/tolak', [ModerationActionController::class, 'reject'])->name('reject');
        Route::post('/{report}/selesai', [ModerationActionController::class, 'resolve'])->name('resolve');
    });
