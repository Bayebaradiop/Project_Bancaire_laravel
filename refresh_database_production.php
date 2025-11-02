<?php

/**
 * Script pour rafraîchir la base de données et relancer les seeders en production
 * ATTENTION: Ce script supprime TOUTES les données !
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "🔄 Rafraîchissement de la base de données en production\n";
echo "=========================================================\n\n";

echo "⚠️  ATTENTION: Vous allez supprimer TOUTES les données !\n";
echo "Appuyez sur Ctrl+C pour annuler dans les 5 secondes...\n\n";

sleep(5);

echo "🗑️  Suppression des données...\n";

try {
    // Exécuter les migrations fresh (drop all tables et recréer)
    echo "   - Suppression des tables...\n";
    Artisan::call('migrate:fresh', ['--force' => true]);
    echo "   ✅ Tables supprimées et recréées\n\n";
    
    // Lancer les seeders
    echo "🌱 Lancement des seeders...\n";
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    echo "   ✅ Seeders exécutés avec succès\n\n";
    
    echo "✅ Rafraîchissement terminé avec succès !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
