<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/sites/{site}', [SiteController::class, 'show'])
    ->whereNumber('site')
    ->name('sites.show');

Route::get('/sites/{site}/laporan/{report}', [SiteController::class, 'report'])
    ->whereNumber(['site', 'report'])
    ->name('sites.reports.show');
