<?php
/**
 * Script de diagnostic en temps réel pour le compte CP9710061062
 * À exécuter sur Render Shell
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$numeroCompte = 'CP9710061062';

echo "========================================\n";
echo "🔍 DIAGNOSTIC COMPTE: {$numeroCompte}\n";
echo "========================================\n\n";

// 1. Vérifier que le compte existe
echo "1️⃣ Vérification du compte dans la base...\n";
$compte = DB::table('comptes')->where('numeroCompte', $numeroCompte)->first();

if (!$compte) {
    echo "❌ Compte introuvable dans la base de données!\n";
    exit(1);
}

echo "✅ Compte trouvé:\n";
echo "   ID: {$compte->id}\n";
echo "   Client ID: {$compte->client_id}\n";
echo "   Créé le: {$compte->created_at}\n\n";

// 2. Vérifier le client et l'email
echo "2️⃣ Vérification du client...\n";
$client = DB::table('clients')->where('id', $compte->client_id)->first();

if (!$client) {
    echo "❌ Client introuvable!\n";
    exit(1);
}

echo "✅ Client trouvé:\n";
echo "   ID: {$client->id}\n";
echo "   User ID: {$client->user_id}\n\n";

// 3. Vérifier l'utilisateur
echo "3️⃣ Vérification de l'utilisateur...\n";
$user = DB::table('users')->where('id', $client->user_id)->first();

if (!$user) {
    echo "❌ Utilisateur introuvable!\n";
    exit(1);
}

echo "✅ Utilisateur trouvé:\n";
echo "   Email: {$user->email}\n";
echo "   Nom: {$user->nomComplet}\n\n";

// 4. Vérifier les jobs en queue
echo "4️⃣ Vérification de la queue...\n";
$pendingJobs = DB::table('jobs')->count();
echo "   Jobs en attente: {$pendingJobs}\n";

if ($pendingJobs > 0) {
    echo "\n   📋 Liste des jobs:\n";
    $jobs = DB::table('jobs')->select('id', 'queue', 'attempts', 'created_at')->get();
    foreach ($jobs as $job) {
        echo "   - Job #{$job->id} | Queue: {$job->queue} | Tentatives: {$job->attempts} | Créé: {$job->created_at}\n";
    }
}

echo "\n";

// 5. Vérifier les failed jobs
echo "5️⃣ Vérification des jobs échoués...\n";
$failedJobs = DB::table('failed_jobs')
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get();

if ($failedJobs->isEmpty()) {
    echo "✅ Aucun job échoué récent\n";
} else {
    echo "⚠️  {$failedJobs->count()} jobs échoués trouvés:\n\n";
    foreach ($failedJobs as $job) {
        echo "   Job #{$job->id}:\n";
        echo "   Connection: {$job->connection}\n";
        echo "   Queue: {$job->queue}\n";
        echo "   Échoué le: {$job->failed_at}\n";
        
        // Extraire juste les premières lignes de l'exception
        $exceptionLines = explode("\n", $job->exception);
        echo "   Erreur: " . trim($exceptionLines[0]) . "\n";
        if (isset($exceptionLines[1])) {
            echo "   " . trim($exceptionLines[1]) . "\n";
        }
        echo "\n";
    }
}

// 6. Vérifier les logs Laravel récents
echo "6️⃣ Logs Laravel récents (liés au compte)...\n";
$logFile = storage_path('logs/laravel.log');

if (file_exists($logFile)) {
    $logContent = shell_exec("grep -i '{$numeroCompte}' {$logFile} | tail -20");
    
    if (empty($logContent)) {
        echo "⚠️  Aucun log trouvé pour ce numéro de compte\n";
        echo "   Recherche de logs 'CompteCreated'...\n\n";
        $logContent = shell_exec("grep -i 'CompteCreated' {$logFile} | tail -10");
    }
    
    if (!empty($logContent)) {
        echo "   Derniers logs:\n";
        echo "   " . str_replace("\n", "\n   ", trim($logContent)) . "\n";
    }
} else {
    echo "❌ Fichier de log introuvable\n";
}

echo "\n";

// 7. Vérifier le queue worker
echo "7️⃣ Vérification du queue worker...\n";
$workerProcess = shell_exec("ps aux | grep 'queue:work' | grep -v grep");

if (empty($workerProcess)) {
    echo "❌ PROBLÈME: Queue worker ne tourne PAS!\n";
    echo "   → Le queue worker doit être démarré pour traiter les jobs\n";
    echo "   → Vérifiez que Supervisor est actif\n";
} else {
    echo "✅ Queue worker actif:\n";
    echo "   " . trim($workerProcess) . "\n";
}

echo "\n";

// 8. Résumé et recommandations
echo "========================================\n";
echo "📊 RÉSUMÉ DU DIAGNOSTIC\n";
echo "========================================\n\n";

$issues = [];
$recommendations = [];

if ($pendingJobs > 0) {
    $issues[] = "Des jobs sont en attente dans la queue";
    $recommendations[] = "Vérifier que le queue worker traite les jobs : watch -n 1 'php artisan queue:monitor'";
}

if (!$failedJobs->isEmpty()) {
    $issues[] = "{$failedJobs->count()} job(s) ont échoué";
    $recommendations[] = "Analyser les erreurs : php artisan queue:failed";
    $recommendations[] = "Réessayer les jobs : php artisan queue:retry all";
}

if (empty($workerProcess)) {
    $issues[] = "Queue worker ne tourne pas";
    $recommendations[] = "Redémarrer Supervisor ou le service Render";
}

if (empty($issues)) {
    echo "✅ Aucun problème détecté au niveau infrastructure\n\n";
    echo "⚠️  Si l'email n'est pas arrivé, vérifiez:\n";
    echo "   1. Les logs Laravel pour voir si l'Event a été dispatché\n";
    echo "   2. Le dossier Spam de {$user->email}\n";
    echo "   3. Les credentials SMTP dans les variables d'environnement\n";
} else {
    echo "❌ PROBLÈMES DÉTECTÉS:\n";
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". {$issue}\n";
    }
    
    echo "\n";
    echo "🔧 RECOMMANDATIONS:\n";
    foreach ($recommendations as $i => $rec) {
        echo "   " . ($i + 1) . ". {$rec}\n";
    }
}

echo "\n========================================\n";
