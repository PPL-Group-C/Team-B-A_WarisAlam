<?php
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\ReportVerificationController;

// Public pages (identity masking happens in the views/resource).
Route::get('/laporan', [DamageReportController::class, 'index'])->name('reports.index');
Route::get('/laporan/{report}', [DamageReportController::class, 'show'])
    ->whereNumber('report')->name('reports.show');

Route::middleware('auth')->group(function () {
    Route::get('/laporan/baru', [DamageReportController::class, 'create'])->name('reports.create');
    Route::post('/laporan', [DamageReportController::class, 'store'])->name('reports.store');

    Route::post('/laporan/{report}/verifikasi', [ReportVerificationController::class, 'store'])
        ->whereNumber('report')->name('reports.verify');
    Route::delete('/laporan/{report}/verifikasi', [ReportVerificationController::class, 'destroy'])
        ->whereNumber('report')->name('reports.unverify');
});
