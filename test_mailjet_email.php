<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Client;
use App\Models\Compte;
use App\Mail\CompteCreatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

echo "🧪 Test d'envoi d'email avec Mailjet\n";
echo "=====================================\n\n";

// Nettoyer les tests précédents
DB::table('clients')->where('email', 'diopbara488@gmail.com')->delete();

// Créer un vrai client dans la DB
$client = Client::create([
    'titulaire' => 'Test Mailjet User',
    'nci' => '1122334455667',
    'email' => 'diopbara488@gmail.com',
    'telephone' => '+221771234567',
    'adresse' => 'Test Address Dakar'
]);

echo "✅ Client créé: ID {$client->id}\n";

// Créer un vrai compte dans la DB
$compte = Compte::create([
    'numeroCompte' => 'CP' . rand(1000000000, 9999999999),
    'type' => 'epargne',
    'solde' => 0,
    'devise' => 'FCFA',
    'statut' => 'actif',
    'client_id' => $client->id
]);

echo "✅ Compte créé: {$compte->numeroCompte}\n\n";

// Paramètres de test
$password = 'TestPass123!';
$code = '123456';

echo "📧 Envoi de l'email à : {$client->email}\n";
echo "📝 Compte : {$compte->numeroCompte}\n";
echo "🔑 Code : {$code}\n\n";

try {
    Mail::to($client->email)->send(new CompteCreatedMail($compte, $password, $code));
    echo "✅ Email envoyé avec succès!\n";
    echo "📬 Vérifiez la boîte mail : {$client->email}\n\n";
} catch (\Exception $e) {
    echo "❌ Erreur lors de l'envoi : " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Nettoyer en cas d'erreur
    $compte->delete();
    $client->delete();
    exit(1);
}

// Nettoyer après le test
echo "🧹 Nettoyage des données de test...\n";
$compte->delete();
$client->delete();
echo "✅ Nettoyage terminé\n";
