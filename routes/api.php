<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CompteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware(['logging', 'track.requests'])->group(function () {
    
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is running',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Routes Comptes (publiques pour l'instant)
    Route::prefix('comptes')->group(function () {
        Route::get('/', [CompteController::class, 'index'])->name('comptes.index');
        Route::post('/', [CompteController::class, 'store'])->name('comptes.store');
        // Utilise Route Model Binding avec 'compte:numeroCompte'
        Route::get('/numero/{compte:numeroCompte}', [CompteController::class, 'showByNumero'])->name('comptes.show.numero');
    });
});


