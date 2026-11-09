<?php
// Add to routes/web.php

use App\Http\Controllers\DamageReportController;

Route::middleware('auth')->group(function () {
    Route::get('/laporan/baru', [DamageReportController::class, 'create'])->name('reports.create');
    Route::post('/laporan', [DamageReportController::class, 'store'])->name('reports.store');
});
