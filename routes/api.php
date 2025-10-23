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
    
    // Route de debug temporaire pour Swagger
    Route::get('/swagger-debug', function () {
        try {
            $documentation = 'default';
            $urlToDocs = route('l5-swagger.'.$documentation.'.docs', [], true);
            return response()->json([
                'success' => true,
                'urlToDocs' => $urlToDocs,
                'config' => [
                    'l5_swagger_const_host' => config('l5-swagger.defaults.constants.L5_SWAGGER_CONST_HOST'),
                    'l5_swagger_base_path' => config('l5-swagger.defaults.paths.base'),
                    'app_url' => config('app.url'),
                    'app_env' => config('app.env'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    });
    
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

