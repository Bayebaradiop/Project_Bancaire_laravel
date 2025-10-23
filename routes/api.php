<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CompteController;



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
    
    // Route de debug pour essayer de render la vue Swagger UI
    Route::get('/swagger-render-test', function () {
        try {
            $documentation = 'default';
            $urlToDocs = route('l5-swagger.'.$documentation.'.docs', [], true);
            $configUrl = null;
            $validatorUrl = null;
            $operationsSorter = null;
            $useAbsolutePath = config('l5-swagger.defaults.paths.use_absolute_path', true);
            
            // Essayer de rendre la vue
            $html = view('l5-swagger::index', compact(
                'documentation',
                'urlToDocs',
                'configUrl',
                'validatorUrl',
                'operationsSorter',
                'useAbsolutePath'
            ))->render();
            
            return response($html)
                ->header('Content-Type', 'text/html; charset=utf-8');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
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

 
        // Routes Comptes
        Route::prefix('comptes')->group(function () {
            Route::get('/', [CompteController::class, 'index'])->name('comptes.index');
            Route::get('/numero/{numero}', [CompteController::class, 'showByNumero'])->name('comptes.show.numero');
        });
        
    // });
});

