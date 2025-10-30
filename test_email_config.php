#!/usr/bin/env php
<?php

/**
 * Script de test pour vérifier la configuration email
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST CONFIGURATION EMAIL ===" . PHP_EOL;
echo PHP_EOL;

// Vérifier les variables d'environnement
echo "Configuration Mail:" . PHP_EOL;
echo "  MAIL_MAILER: " . env('MAIL_MAILER') . PHP_EOL;
echo "  MAIL_HOST: " . env('MAIL_HOST') . PHP_EOL;
echo "  MAIL_PORT: " . env('MAIL_PORT') . PHP_EOL;
echo "  MAIL_USERNAME: " . env('MAIL_USERNAME') . PHP_EOL;
echo "  MAIL_ENCRYPTION: " . env('MAIL_ENCRYPTION') . PHP_EOL;
echo "  MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS') . PHP_EOL;
echo "  MAIL_FROM_NAME: " . env('MAIL_FROM_NAME') . PHP_EOL;
echo PHP_EOL;

// Vérifier la configuration Laravel
$config = config('mail');
echo "Laravel Mail Config:" . PHP_EOL;
echo "  Default: " . $config['default'] . PHP_EOL;
echo "  Mailer Host: " . $config['mailers']['smtp']['host'] . PHP_EOL;
echo "  Mailer Port: " . $config['mailers']['smtp']['port'] . PHP_EOL;
echo "  Mailer Username: " . $config['mailers']['smtp']['username'] . PHP_EOL;
echo PHP_EOL;

// Test de connexion SMTP
echo "Test de connexion SMTP..." . PHP_EOL;
try {
    $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
        env('MAIL_HOST'),
        env('MAIL_PORT'),
        env('MAIL_ENCRYPTION') === 'tls'
    );
    
    $transport->setUsername(env('MAIL_USERNAME'));
    $transport->setPassword(env('MAIL_PASSWORD'));
    
    $transport->start();
    echo "✅ Connexion SMTP réussie !" . PHP_EOL;
    $transport->stop();
} catch (\Exception $e) {
    echo "❌ Erreur de connexion SMTP: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;
echo "=== FIN DU TEST ===" . PHP_EOL;
