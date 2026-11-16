<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('view-reporter-identity', function (User $user, DamageReport $report) {
    return $user->id === $report->user_id                              // the reporter themself
        || in_array(data_get($user, 'role'), ['officer', 'admin'], true); // BPK/BKSDA staff
        });
    }
}
