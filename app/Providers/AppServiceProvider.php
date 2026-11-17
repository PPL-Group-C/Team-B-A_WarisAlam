<?php

namespace App\Providers;

use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
            return $user->id === $report->user_id                               // the reporter themself
                || in_array(data_get($user, 'role'), ['officer', 'admin'], true); // BPK/BKSDA staff
        });

        // F08 Moderasi: satu-satunya pintu otorisasi moderator.
        Gate::define('moderate-reports', function (User $user): bool {
            if (method_exists($user, 'hasRole')) {
                return $user->hasRole(['moderator', 'admin']);
            }

            // Fallback sebelum spatie/laravel-permission terpasang.
            return in_array(strtolower($user->email), config('moderation.moderator_emails', []), true);
        });
    }
}
