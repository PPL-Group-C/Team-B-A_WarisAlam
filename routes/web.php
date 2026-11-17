<?php

use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    Route::get('/dev-login', function () {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'dev@warisalam.test'],
            ['name' => 'Dev Reporter', 'password' => bcrypt('password')]
        );

        auth()->login($user);

        return redirect()->route('reports.create');
    });
}

Route::middleware('auth')->group(function () {
    Route::get('/laporan/baru', [DamageReportController::class, 'create'])->name('reports.create');
    Route::post('/laporan', [DamageReportController::class, 'store'])->name('reports.store');
});

Route::view('/map', 'map.index')->name('map.index');
Route::get('/map/sites', [MapController::class, 'sites'])->name('map.sites');