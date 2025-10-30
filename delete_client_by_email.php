<?php
/**
 * Script pour supprimer un client par email
 * Usage: php delete_client_by_email.php tt3435336@gmail.com
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;

// Récupérer l'email depuis les arguments
$email = $argv[1] ?? 'tt3435336@gmail.com';

echo "========================================\n";
echo "SUPPRESSION CLIENT PAR EMAIL\n";
echo "========================================\n\n";

echo "Email à supprimer: $email\n\n";

// Rechercher l'utilisateur
$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ Aucun utilisateur trouvé avec cet email\n";
    exit(0);
}

echo "✅ Utilisateur trouvé: {$user->nomComplet} (ID: {$user->id})\n";

// Rechercher le client
$client = $user->client;

if (!$client) {
    echo "⚠️  Pas de client associé, suppression de l'utilisateur seulement...\n";
    $user->forceDelete();
    echo "✅ Utilisateur supprimé\n";
    exit(0);
}

echo "✅ Client trouvé: {$client->titulaire} (ID: {$client->id})\n";

// Compter les comptes
$comptesCount = $client->comptes()->withTrashed()->count();
echo "📋 Nombre de comptes: $comptesCount\n\n";

if ($comptesCount > 0) {
    echo "Suppression des comptes...\n";
    foreach ($client->comptes()->withTrashed()->get() as $compte) {
        echo "  - Suppression compte: {$compte->numeroCompte}\n";
        $compte->forceDelete();
    }
    echo "✅ Tous les comptes supprimés\n\n";
}

echo "Suppression du client...\n";
$client->forceDelete();
echo "✅ Client supprimé\n\n";

echo "Suppression de l'utilisateur...\n";
$user->forceDelete();
echo "✅ Utilisateur supprimé\n\n";

echo "========================================\n";
echo "✅ SUPPRESSION COMPLÈTE RÉUSSIE\n";
echo "========================================\n";
