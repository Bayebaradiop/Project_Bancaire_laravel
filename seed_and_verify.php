<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "========================================\n";
echo "🚀 SEED + VÉRIFICATION DES DOUBLONS\n";
echo "========================================\n\n";

// ÉTAPE 1 : Vérifier l'état AVANT seeding
echo "📊 ÉTAPE 1 : État AVANT seeding\n";
echo "----------------------------------------\n";

try {
    $usersAvant = DB::connection('pgsql')->table('users')->count();
    $comptesAvant = DB::connection('pgsql')->table('comptes')->count();
    
    echo "PostgreSQL (Render) :\n";
    echo "  - Users : $usersAvant\n";
    echo "  - Comptes : $comptesAvant\n\n";
    
    // Vérifier Neon
    try {
        DB::connection('neon')->getPdo();
        $tablesNeon = DB::connection('neon')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        $tableNames = array_column($tablesNeon, 'tablename');
        
        echo "Neon (Archive Cloud) :\n";
        echo "  - Tables disponibles : " . implode(', ', $tableNames) . "\n";
        
        // Vérifier dans archives_comptes ou comptes_archives
        $archiveTableName = null;
        if (in_array('archives_comptes', $tableNames)) {
            $archiveTableName = 'archives_comptes';
        } elseif (in_array('comptes_archives', $tableNames)) {
            $archiveTableName = 'comptes_archives';
        }
        
        if ($archiveTableName) {
            $comptesNeon = DB::connection('neon')->table($archiveTableName)->count();
            echo "  - Comptes archivés ($archiveTableName) : $comptesNeon\n";
        } else {
            echo "  - Aucune table d'archives trouvée\n";
        }
    } catch (\Exception $e) {
        echo "⚠️  Neon non accessible : " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

echo "\n";

// ÉTAPE 2 : Exécuter le seeding
echo "🌱 ÉTAPE 2 : Exécution du seeding\n";
echo "----------------------------------------\n";
echo "Exécution de : php artisan db:seed --force\n\n";

try {
    Artisan::call('db:seed', ['--force' => true]);
    echo Artisan::output();
    echo "✅ Seeding terminé avec succès\n\n";
} catch (\Exception $e) {
    echo "❌ Erreur lors du seeding : " . $e->getMessage() . "\n\n";
    exit(1);
}

// ÉTAPE 3 : Vérifier l'état APRÈS seeding
echo "📊 ÉTAPE 3 : État APRÈS seeding\n";
echo "----------------------------------------\n";

try {
    $usersApres = DB::connection('pgsql')->table('users')->count();
    $comptesApres = DB::connection('pgsql')->table('comptes')->count();
    
    echo "PostgreSQL (Render) :\n";
    echo "  - Users : $usersApres (+" . ($usersApres - $usersAvant) . ")\n";
    echo "  - Comptes : $comptesApres (+" . ($comptesApres - $comptesAvant) . ")\n\n";
    
    // Afficher les utilisateurs créés
    if ($usersApres > 0) {
        echo "👥 Utilisateurs créés :\n";
        $users = DB::connection('pgsql')->table('users')->select('nomComplet', 'email', 'role')->get();
        foreach ($users as $user) {
            echo "  - {$user->nomComplet} ({$user->email}) - Rôle: {$user->role}\n";
        }
        echo "\n";
    }
    
    // Afficher les comptes créés
    if ($comptesApres > 0) {
        echo "💰 Comptes créés :\n";
        $comptes = DB::connection('pgsql')
            ->table('comptes')
            ->join('clients', 'comptes.client_id', '=', 'clients.id')
            ->select('comptes.numeroCompte', 'comptes.type', 'comptes.statut', 'clients.titulaire')
            ->limit(10)
            ->get();
        
        foreach ($comptes as $compte) {
            echo "  - {$compte->numeroCompte} | {$compte->type} | {$compte->statut} | {$compte->titulaire}\n";
        }
        
        if ($comptesApres > 10) {
            echo "  ... et " . ($comptesApres - 10) . " autres comptes\n";
        }
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

// ÉTAPE 4 : VÉRIFICATION DES DOUBLONS entre PostgreSQL et Neon
echo "🔍 ÉTAPE 4 : DÉTECTION DES DOUBLONS\n";
echo "----------------------------------------\n";

try {
    // Récupérer les numéros de comptes dans PostgreSQL
    $numerosPostgres = DB::connection('pgsql')
        ->table('comptes')
        ->pluck('numeroCompte')
        ->toArray();
    
    echo "Comptes dans PostgreSQL : " . count($numerosPostgres) . "\n";
    
    // Vérifier dans Neon
    try {
        DB::connection('neon')->getPdo();
        
        // Chercher dans archives_comptes ou comptes_archives
        $archiveTableName = null;
        $tablesNeon = DB::connection('neon')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        $tableNames = array_column($tablesNeon, 'tablename');
        
        if (in_array('archives_comptes', $tableNames)) {
            $archiveTableName = 'archives_comptes';
        } elseif (in_array('comptes_archives', $tableNames)) {
            $archiveTableName = 'comptes_archives';
        }
        
        if ($archiveTableName) {
            // Utiliser le nom de colonne en minuscules pour Neon
            $numerosNeon = DB::connection('neon')
                ->table($archiveTableName)
                ->pluck(DB::raw('LOWER("numerocompte")'))
                ->toArray();
            
            echo "Comptes dans Neon ($archiveTableName) : " . count($numerosNeon) . "\n\n";
            
            // Chercher les doublons
            $doublons = array_intersect($numerosPostgres, $numerosNeon);
            
            if (count($doublons) > 0) {
                echo "⚠️  ATTENTION ! " . count($doublons) . " DOUBLON(S) DÉTECTÉ(S) !\n";
                echo "----------------------------------------\n";
                echo "Les comptes suivants existent dans les DEUX bases :\n\n";
                
                foreach ($doublons as $numeroCompte) {
                    // Infos PostgreSQL
                    $comptePostgres = DB::connection('pgsql')
                        ->table('comptes')
                        ->where('numeroCompte', $numeroCompte)
                        ->first();
                    
                    // Infos Neon
                    $compteNeon = DB::connection('neon')
                        ->table($archiveTableName)
                        ->where('numeroCompte', $numeroCompte)
                        ->first();
                    
                    echo "🔴 $numeroCompte\n";
                    echo "   PostgreSQL : Statut = " . ($comptePostgres->statut ?? 'N/A') . "\n";
                    echo "   Neon       : Statut = " . ($compteNeon->statut ?? 'N/A') . "\n";
                    echo "   Type       : " . ($comptePostgres->type ?? 'N/A') . "\n\n";
                }
                
                echo "🔧 RECOMMANDATION :\n";
                echo "Ces comptes ne devraient être que dans UNE SEULE base :\n";
                echo "  - PostgreSQL = comptes actifs, bloqués, fermés\n";
                echo "  - Neon = comptes archivés (historique)\n";
                echo "\nAction requise : Vérifier la logique d'archivage.\n";
                
            } else {
                echo "✅ AUCUN DOUBLON !\n";
                echo "Séparation parfaite entre PostgreSQL et Neon.\n";
                echo "  - PostgreSQL : comptes actifs/bloqués/fermés\n";
                echo "  - Neon : comptes archivés uniquement\n";
            }
            
        } else {
            echo "⚠️  Aucune table d'archives dans Neon\n";
            echo "Les comptes archivés seront créés lors du premier blocage.\n";
        }
        
    } catch (\Exception $e) {
        echo "⚠️  Impossible de vérifier Neon : " . $e->getMessage() . "\n";
        echo "Note : Cela est normal si aucun compte n'a encore été archivé.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur lors de la vérification : " . $e->getMessage() . "\n";
}

echo "\n";
echo "========================================\n";
echo "✅ PROCESSUS TERMINÉ\n";
echo "========================================\n";
