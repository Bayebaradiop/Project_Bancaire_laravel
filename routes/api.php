<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CompteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    
    // Routes publiques (sans authentification)
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is running',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Routes protégées par authentification
    // TODO: Réactiver l'authentification plus tard
    // Route::middleware(['auth:sanctum'])->group(function () {
        
        // Routes Comptes
        Route::prefix('comptes')->group(function () {
            Route::get('/', [CompteController::class, 'index'])->name('comptes.index');
            Route::get('/numero/{numero}', [CompteController::class, 'showByNumero'])->name('comptes.show.numero');
        });
        
    // });
});

