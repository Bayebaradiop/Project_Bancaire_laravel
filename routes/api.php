<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CompteController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    
    // Health check endpoint (public)
    Route::get('/health', [HealthController::class, 'check']);

    /*
    |--------------------------------------------------------------------------
    | Routes d'Authentification (publiques)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('auth.logout');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Comptes Protégées (auth:api)
    |--------------------------------------------------------------------------
    */
    Route::prefix('comptes')->middleware('auth:api')->group(function () {
        // 1. Admin peut récupérer la liste de tous les comptes
        // 2. Client peut récupérer la liste de ses propres comptes
        Route::get('/', [CompteController::class, 'index'])->name('comptes.index');

        Route::post('/', [CompteController::class, 'store'])->name('comptes.store');
        
        // US 2.3 - Mettre à jour les informations d'un compte (Admin uniquement)
        Route::patch('/{compteId}', [CompteController::class, 'update'])->name('comptes.update')
            ->where('compteId', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
        
        // Récupérer un compte par ID (US 2.1 - Dual database: PostgreSQL -> Neon)
        Route::get('/{id}', [CompteController::class, 'show'])->name('comptes.show')
            ->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
        
        Route::get('/numero/{numero}', [CompteController::class, 'showByNumero'])->name('comptes.show.numero');

        // Routes pour les archives (cloud)
        Route::get('/archives', [CompteController::class, 'archives'])->name('comptes.archives');
        Route::post('/{numeroCompte}/archive', [CompteController::class, 'archive'])->name('comptes.archive');

        // Routes pour le blocage/déblocage (US 2.5)
        Route::post('/{compteId}/bloquer', [CompteController::class, 'bloquer'])->name('comptes.bloquer');
        Route::post('/{compteId}/debloquer', [CompteController::class, 'debloquer'])->name('comptes.debloquer');

        // Routes pour la suppression et restauration (dual database)
        Route::delete('/{numeroCompte}', [CompteController::class, 'destroy'])->name('comptes.destroy');
        Route::post('/restore/{id}', [CompteController::class, 'restore'])->name('comptes.restore');
    });
});

