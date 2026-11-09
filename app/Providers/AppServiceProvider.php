<?php

namespace App\Providers;

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
