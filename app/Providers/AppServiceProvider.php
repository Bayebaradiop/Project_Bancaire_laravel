<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Compte;
use App\Observers\CompteObserver;
use App\Repositories\TransactionRepositoryInterface;
use App\Repositories\TransactionRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind repository interfaces to concrete implementations
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
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
