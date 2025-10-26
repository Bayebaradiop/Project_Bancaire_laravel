<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CompteController;

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
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is running',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Comptes (publiques pour cette branche)
    |--------------------------------------------------------------------------
    */
    Route::prefix('comptes')->group(function () {
        Route::get('/', [CompteController::class, 'index'])->name('comptes.index');
        Route::post('/', [CompteController::class, 'store'])->name('comptes.store');
        Route::get('/numero/{numero}', [CompteController::class, 'showByNumero'])->name('comptes.show.numero');
        
        // Routes pour les archives (cloud)
        Route::get('/archives', [CompteController::class, 'archives'])->name('comptes.archives');
        Route::post('/{numeroCompte}/archive', [CompteController::class, 'archive'])->name('comptes.archive');
    });
});

