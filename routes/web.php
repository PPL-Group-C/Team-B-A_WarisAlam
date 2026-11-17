<?php
// Add to routes/web.php

use App\Http\Controllers\DamageReportController;
if (app()->environment('local')) {
    Route::get('/dev-login', function () {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'dev@warisalam.test'],
            ['name' => 'Dev Reporter', 'password' => bcrypt('password')]
        );

        auth()->login($user);

        return redirect()->route('reports.create');
    });
};
