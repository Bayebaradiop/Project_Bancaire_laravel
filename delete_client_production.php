<?php

/**
 * Script pour supprimer un client en production via la base de données
 */

// Charger l'autoloader de Laravel
require __DIR__.'/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

// Démarrer le kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Email du client à supprimer
$email = 'diopbara488@gmail.com';

echo "Recherche du client avec l'email: {$email}\n";

// Trouver le client
$client = DB::table('clients')->where('email', $email)->first();

if (!$client) {
    echo "❌ Aucun client trouvé avec cet email.\n";
    exit(1);
}

echo "✓ Client trouvé - ID: {$client->id}\n";

// Supprimer les comptes associés
$comptesDeleted = DB::table('comptes')->where('client_id', $client->id)->delete();
echo "✓ {$comptesDeleted} compte(s) supprimé(s)\n";

// Supprimer le client
$clientDeleted = DB::table('clients')->where('id', $client->id)->delete();
echo "✓ Client supprimé\n";

echo "\n✅ Suppression terminée avec succès!\n";
