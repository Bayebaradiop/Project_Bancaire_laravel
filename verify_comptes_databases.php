<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Compte;

echo "========================================\n";
echo "🔍 VÉRIFICATION DES COMPTES\n";
echo "========================================\n\n";

// 1. Comptes dans PostgreSQL (Render - Base principale)
echo "📊 POSTGRESQL (Render) - Base principale :\n";
echo "----------------------------------------\n";
$comptesPostgres = DB::connection('pgsql')->table('comptes')->get();
echo "Total comptes : " . $comptesPostgres->count() . "\n";

if ($comptesPostgres->count() > 0) {
    echo "\nDétails des comptes :\n";
    foreach ($comptesPostgres as $compte) {
        echo sprintf(
            "- %s | %s | Statut: %s | Client: %s\n",
            $compte->numeroCompte,
            $compte->type,
            $compte->statut,
            $compte->client_id
        );
    }
}

echo "\n";

// 2. Comptes dans Neon (Archive Cloud)
echo "☁️  NEON (Archive Cloud) :\n";
echo "----------------------------------------\n";

try {
    // Vérifier si la connexion Neon est configurée
    if (!config('database.connections.neon')) {
        echo "❌ Connexion Neon non configurée\n";
    } else {
        // Tester la connexion
        DB::connection('neon')->getPdo();
        
        // Vérifier si la table existe
        $tablesNeon = DB::connection('neon')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        $tableNames = array_column($tablesNeon, 'tablename');
        
        if (in_array('comptes', $tableNames)) {
            $comptesNeon = DB::connection('neon')->table('comptes')->get();
            echo "Total comptes archivés : " . $comptesNeon->count() . "\n";
            
            if ($comptesNeon->count() > 0) {
                echo "\nDétails des comptes archivés :\n";
                foreach ($comptesNeon as $compte) {
                    echo sprintf(
                        "- %s | %s | Statut: %s | Archivé le: %s\n",
                        $compte->numeroCompte,
                        $compte->type,
                        $compte->statut ?? 'N/A',
                        $compte->created_at ?? 'N/A'
                    );
                }
            }
        } else {
            echo "⚠️  Table 'comptes' n'existe pas encore dans Neon\n";
            echo "Tables disponibles : " . implode(', ', $tableNames) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur de connexion à Neon : " . $e->getMessage() . "\n";
    echo "Vérifiez les variables d'environnement NEON_DB_*\n";
}

echo "\n";

// 3. Vérifier les doublons (comptes présents dans les DEUX bases)
echo "🔎 ANALYSE DES DOUBLONS :\n";
echo "----------------------------------------\n";

try {
    if (isset($comptesPostgres) && isset($comptesNeon)) {
        $numerosPostgres = $comptesPostgres->pluck('numeroCompte')->toArray();
        $numerosNeon = $comptesNeon->pluck('numeroCompte')->toArray();
        
        $doublons = array_intersect($numerosPostgres, $numerosNeon);
        
        if (count($doublons) > 0) {
            echo "⚠️  ATTENTION ! " . count($doublons) . " compte(s) présent(s) dans les DEUX bases :\n";
            foreach ($doublons as $numero) {
                echo "  - $numero\n";
            }
            echo "\n";
            echo "🔧 RECOMMANDATION :\n";
            echo "Ces comptes devraient être soit dans PostgreSQL (actifs), soit dans Neon (archivés).\n";
            echo "Vérifiez la logique d'archivage.\n";
        } else {
            echo "✅ Aucun doublon détecté. Séparation correcte entre bases.\n";
        }
    }
} catch (\Exception $e) {
    echo "⚠️  Impossible de vérifier les doublons : " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Statistiques par statut
echo "📈 STATISTIQUES PAR STATUT (PostgreSQL) :\n";
echo "----------------------------------------\n";
if ($comptesPostgres->count() > 0) {
    $stats = $comptesPostgres->groupBy('statut');
    foreach ($stats as $statut => $comptes) {
        echo sprintf("- %s : %d compte(s)\n", $statut, $comptes->count());
    }
} else {
    echo "Aucun compte dans PostgreSQL\n";
}

echo "\n";
echo "========================================\n";
echo "✅ VÉRIFICATION TERMINÉE\n";
echo "========================================\n";
