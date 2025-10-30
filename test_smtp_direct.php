<?php
/**
 * Test SMTP Direct - Sans Queue
 * 
 * Ce script envoie un email immédiatement pour tester la connexion SMTP
 * À exécuter sur Render Shell OU en local
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Mail\CompteCreatedMail;
use App\Models\Compte;

echo "========================================\n";
echo "TEST SMTP DIRECT - SANS QUEUE\n";
echo "========================================\n\n";

// Configuration
$testEmail = 'nabuudione@gmail.com';

echo "Configuration SMTP:\n";
echo "  Host: " . config('mail.mailers.smtp.host') . "\n";
echo "  Port: " . config('mail.mailers.smtp.port') . "\n";
echo "  Username: " . config('mail.mailers.smtp.username') . "\n";
echo "  Encryption: " . config('mail.mailers.smtp.encryption') . "\n";
echo "  From: " . config('mail.from.address') . "\n\n";

// Test 1: Email brut simple
echo "Test 1: Envoi d'un email brut simple...\n";
try {
    Mail::raw('Ceci est un test SMTP direct depuis ' . config('app.env') . '.', function ($message) use ($testEmail) {
        $message->to($testEmail)
                ->subject('Test SMTP - Faysany Banque');
    });
    echo "✅ Email brut envoyé avec succès!\n\n";
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Email brut avec template HTML simple
echo "Test 2: Envoi d'un email HTML personnalisé...\n";
try {
    Mail::send([], [], function ($message) use ($testEmail) {
        $message->to($testEmail)
                ->subject('Test SMTP - Template Faysany Banque')
                ->html('
                    <html>
                    <body style="font-family: Arial, sans-serif; padding: 20px;">
                        <h1 style="color: #667eea;">Bienvenue sur Faysany Banque</h1>
                        <p>Bonjour <strong>Test SMTP Direct</strong>,</p>
                        <p>Votre compte a été créé avec succès!</p>
                        <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea;">
                            <h3>Vos identifiants</h3>
                            <p><strong>Numéro de compte:</strong> CP0000000000</p>
                            <p><strong>Mot de passe temporaire:</strong> TestPassword123!</p>
                            <p><strong>Code de validation:</strong> 1234</p>
                        </div>
                        <p>Ceci est un test d\'envoi SMTP direct depuis <strong>' . config('app.env') . '</strong>.</p>
                        <p>Cordialement,<br>L\'équipe Faysany Banque</p>
                    </body>
                    </html>
                ');
    });
    
    echo "✅ Email HTML personnalisé envoyé avec succès!\n\n";
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "========================================\n";
echo "✅ TOUS LES TESTS RÉUSSIS!\n";
echo "========================================\n";
echo "\nVérifiez la boîte mail: $testEmail\n";
echo "L'email devrait être arrivé IMMÉDIATEMENT (envoi direct, pas de queue)\n\n";
