<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RenderConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // On Render, auto-detect the APP_URL if not properly set
        if (app()->environment('production') && (
            empty(config('app.url')) || 
            config('app.url') === 'http://localhost' ||
            strpos(config('app.url'), 'votre-app') !== false
        )) {
            $scheme = $this->request()->getScheme() ?? 'https';
            $host = $this->request()->getHost();
            $url = "{$scheme}://{$host}";
            
            config(['app.url' => $url]);
        }
    }

    private function request()
    {
        return request();
    }
}
