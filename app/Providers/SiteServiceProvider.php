<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Fitur Detail Situs (Raihan). Route-nya dimuat dari sini, bukan dari
 * routes/web.php atau bootstrap/app.php, karena kedua file itu juga diubah
 * anggota lain dan rawan konflik saat merge.
 */
class SiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(base_path('routes/sites.php'));
        });
    }
}
