<?php

/**
 * Script pour nettoyer les clients de test en production
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🧹 Nettoyage des clients de test en production\n";
echo "===============================================\n\n";

$emails = [
    'diopbara488@gmail.com',
    'njaay146@gmail.com',
    'bayebaradiopd@gmail.com',
    'abdoulayely148@gmail.com',
    'diopmata82@gmail.com'
];

$totalDeleted = 0;

foreach ($emails as $email) {
    echo "🔍 Recherche de: {$email}...\n";
    
    $client = DB::table('clients')->where('email', $email)->first();
    
    if ($client) {
        // Supprimer les comptes associés
        $comptesDeleted = DB::table('comptes')->where('client_id', $client->id)->delete();
        
        // Supprimer le client
        DB::table('clients')->where('id', $client->id)->delete();
        
        echo "   ✅ Supprimé: {$comptesDeleted} compte(s)\n";
        $totalDeleted++;
    } else {
        echo "   ℹ️  Non trouvé\n";
    }
}

echo "\n✅ Nettoyage terminé!\n";
echo "📊 Total clients supprimés: {$totalDeleted}\n";
