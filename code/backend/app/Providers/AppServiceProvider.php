<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            if (app()->environment('local', 'testing')) {
                return Limit::none();
            }

            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Produção: 5/min. Local/testing: sem teto (E2E / smoke).
        RateLimiter::for('login', function (Request $request) {
            if ($request->getHost() === '127.0.0.1' || app()->environment('local', 'testing')) {
                return Limit::none();
            }

            return Limit::perMinute(5)->by(strtolower((string) ($request->input('login') ?? $request->input('email'))).'|'.$request->ip());
        });

        RateLimiter::for('mutations', function (Request $request) {
            if (app()->environment('local', 'testing')) {
                return Limit::none();
            }

            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
