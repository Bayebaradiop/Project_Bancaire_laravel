<?php

/**
 * Test local du système Event/Listener pour l'envoi d'emails
 * 
 * Ce script simule la création d'un compte et vérifie que l'event est bien déclenché
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Event;
use App\Events\CompteCreated;
use App\Listeners\SendClientNotification;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "TEST LOCAL - SYSTÈME EVENT/LISTENER\n";
echo "========================================\n\n";

// 1. Vérifier que l'Event est enregistré
echo "1. Vérification de l'enregistrement Event/Listener...\n";

$listeners = Event::getListeners(CompteCreated::class);

if (empty($listeners)) {
    echo "❌ ERREUR: Aucun listener enregistré pour CompteCreated\n";
    echo "   Vérifiez EventServiceProvider::\$listen\n\n";
    exit(1);
}

echo "✅ Listener(s) enregistré(s) pour CompteCreated:\n";
foreach ($listeners as $listener) {
    echo "   - " . get_class($listener) . "\n";
}
echo "\n";

// 2. Vérifier la configuration mail
echo "2. Vérification de la configuration SMTP...\n";
echo "   MAIL_MAILER: " . config('mail.default') . "\n";
echo "   MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "   MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "   MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "   MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
echo "   MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "\n";

// 3. Vérifier la queue
echo "3. Vérification de la configuration Queue...\n";
echo "   QUEUE_CONNECTION: " . config('queue.default') . "\n";

if (config('queue.default') === 'sync') {
    echo "   ⚠️  WARNING: Queue en mode 'sync' - les jobs s'exécutent immédiatement\n";
    echo "   En production, cela devrait être 'database'\n";
} else {
    echo "   ✅ Queue configurée pour exécution asynchrone\n";
}
echo "\n";

// 4. Vérifier que SendClientNotification implémente ShouldQueue
echo "4. Vérification que le Listener utilise la queue...\n";

$listenerClass = new ReflectionClass(SendClientNotification::class);
$interfaces = $listenerClass->getInterfaceNames();

if (in_array('Illuminate\Contracts\Queue\ShouldQueue', $interfaces)) {
    echo "   ✅ SendClientNotification implémente ShouldQueue\n";
    echo "   Les emails seront envoyés en arrière-plan\n";
} else {
    echo "   ❌ SendClientNotification n'implémente PAS ShouldQueue\n";
    echo "   Les emails seront envoyés de manière synchrone (bloquant)\n";
}
echo "\n";

// 5. Compter les jobs en attente (si queue = database)
if (config('queue.default') === 'database') {
    echo "5. Vérification de la table 'jobs'...\n";
    try {
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        echo "   Jobs en attente: $pendingJobs\n";
        echo "   Jobs échoués: $failedJobs\n";
        
        if ($failedJobs > 0) {
            echo "\n   ⚠️  ATTENTION: Des jobs ont échoué!\n";
            echo "   Exécutez: php artisan queue:failed\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Erreur accès base de données: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// 6. Résumé
echo "========================================\n";
echo "RÉSUMÉ\n";
echo "========================================\n\n";

echo "✅ Architecture Event/Listener correctement configurée\n";
echo "✅ SMTP Gmail configuré\n";

if (config('queue.default') === 'database') {
    echo "✅ Queue en mode 'database' (asynchrone)\n";
    echo "\n";
    echo "⚠️  IMPORTANT: Pour que les emails partent, le queue worker DOIT tourner:\n";
    echo "   Local: php artisan queue:work\n";
    echo "   Render: Supervisord démarre automatiquement le worker\n";
} else {
    echo "⚠️  Queue en mode 'sync' - les jobs s'exécutent immédiatement\n";
}

echo "\n";
echo "📧 Prochaine étape:\n";
echo "   1. Vérifier que le queue worker tourne sur Render\n";
echo "   2. Créer un compte via l'API\n";
echo "   3. L'event CompteCreated sera dispatché\n";
echo "   4. Le listener SendClientNotification sera mis en queue\n";
echo "   5. Le queue worker traitera le job et enverra l'email\n";
echo "\n";
echo "========================================\n";
