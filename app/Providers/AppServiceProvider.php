<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Compte;
use App\Observers\CompteObserver;

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
        // Enregistrer l'observer pour archiver automatiquement les comptes fermés/bloqués
        Compte::observe(CompteObserver::class);
    }
}
