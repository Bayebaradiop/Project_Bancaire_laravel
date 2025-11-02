<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CompteController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\UserController;

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
    
    // Test email endpoint (admin only)
    Route::post('/test/email', [\App\Http\Controllers\TestEmailController::class, 'testEmail'])->middleware('auth:api');
    
    // Email diagnostic endpoint (admin only)
    Route::get('/diagnostic/email', function () {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return response()->json([
            'success' => true,
            'email_method' => env('BREVO_API_KEY') ? 'API Brevo' : 'SMTP',
            'mail_config' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
            'env_vars' => [
                'MAIL_MAILER' => env('MAIL_MAILER'),
                'MAIL_HOST' => env('MAIL_HOST'),
                'MAIL_PORT' => env('MAIL_PORT'),
                'MAIL_USERNAME' => env('MAIL_USERNAME'),
                'MAIL_PASSWORD_SET' => env('MAIL_PASSWORD') ? 'YES' : 'NO',
                'BREVO_USERNAME' => env('BREVO_USERNAME'),
                'BREVO_SMTP_KEY_SET' => env('BREVO_SMTP_KEY') ? 'YES' : 'NO',
                'BREVO_API_KEY_SET' => env('BREVO_API_KEY') ? 'YES' : 'NO',
            ],
            'recommendation' => !env('BREVO_API_KEY') ? 'Add BREVO_API_KEY to use API instead of SMTP (ports blocked on Render)' : 'Using API - SMTP variables no longer needed',
        ]);
    })->middleware('auth:api');

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
    | Routes Clients Protégées (auth:api)
    |--------------------------------------------------------------------------
    */
    Route::prefix('clients')->middleware('auth:api')->group(function () {
        // Récupérer un client par numéro de téléphone
        Route::get('/telephone/{telephone}', [UserController::class, 'getByPhone'])->name('clients.getByPhone');
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

        // Création de compte - Admin uniquement
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

        // Routes pour le blocage/déblocage (US 2.5)
        Route::post('/{compteId}/bloquer', [CompteController::class, 'bloquer'])->name('comptes.bloquer');
        Route::post('/{compteId}/debloquer', [CompteController::class, 'debloquer'])->name('comptes.debloquer');

        // Routes pour la suppression et restauration (dual database)
        Route::delete('/{numeroCompte}', [CompteController::class, 'destroy'])->name('comptes.destroy');
        Route::post('/restore/{id}', [CompteController::class, 'restore'])->name('comptes.restore');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Transactions Protégées (auth:api)
    |--------------------------------------------------------------------------
    */
    Route::prefix('transactions')->middleware('auth:api')->group(function () {
        // Liste des transactions (Admin: toutes, Client: ses transactions uniquement)
        Route::get('/', [App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
        
        // Effectuer un dépôt (Admin uniquement)
        Route::post('/depot', [App\Http\Controllers\TransactionController::class, 'depot'])->name('transactions.depot');
        
        // Effectuer un retrait (Admin et Client propriétaire du compte)
        Route::post('/retrait', [App\Http\Controllers\TransactionController::class, 'retrait'])->name('transactions.retrait');
        
        // Effectuer un transfert (Admin et Client propriétaire du compte source)
        Route::post('/transfert', [App\Http\Controllers\TransactionController::class, 'transfert'])->name('transactions.transfert');
        
        // Afficher une transaction spécifique
        Route::get('/{id}', [App\Http\Controllers\TransactionController::class, 'show'])->name('transactions.show')
            ->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
        
        // Annuler une transaction (moins de 24h)
        Route::delete('/{numeroTransaction}', [App\Http\Controllers\TransactionController::class, 'annuler'])->name('transactions.annuler');
    });
});

