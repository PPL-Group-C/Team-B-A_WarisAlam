<?php

use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/map', 'map.index')->name('map.index');
Route::get('/map/sites', [MapController::class, 'sites'])->name('map.sites');
