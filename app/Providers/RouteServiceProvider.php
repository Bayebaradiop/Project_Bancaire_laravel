<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Rate limiting: 1000 requêtes par heure par utilisateur authentifié
        RateLimiter::for('api', function (Request $request) {
            return Limit::perHour(1000)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiting: 100 requêtes par minute par IP
        RateLimiter::for('ip', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // Rate limiting pour le login: 60 tentatives par minute en développement
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
