<?php

/**
 * Script de diagnostic email en production
 * À exécuter via: php test_email_production.php
 */

echo "===========================================\n";
echo "DIAGNOSTIC EMAIL PRODUCTION RENDER\n";
echo "===========================================\n\n";

// Simuler l'environnement Laravel minimal
$envVars = [
    'MAIL_MAILER',
    'MAIL_HOST',
    'MAIL_PORT',
    'MAIL_USERNAME',
    'MAIL_PASSWORD',
    'MAIL_ENCRYPTION',
    'MAIL_FROM_ADDRESS',
    'MAIL_FROM_NAME',
    'BREVO_USERNAME',
    'BREVO_SMTP_KEY',
];

echo "📋 VARIABLES D'ENVIRONNEMENT EMAIL:\n";
echo "-------------------------------------------\n";
foreach ($envVars as $var) {
    $value = getenv($var) ?: 'NON DÉFINIE';
    if (in_array($var, ['MAIL_PASSWORD', 'BREVO_SMTP_KEY'])) {
        $value = $value !== 'NON DÉFINIE' ? substr($value, 0, 10) . '...' : 'NON DÉFINIE';
    }
    echo sprintf("%-20s: %s\n", $var, $value);
}

echo "\n✅ CONFIGURATION ATTENDUE POUR BREVO:\n";
echo "-------------------------------------------\n";
echo "MAIL_MAILER          : brevo (PAS smtp!)\n";
echo "MAIL_HOST            : smtp-relay.brevo.com\n";
echo "MAIL_PORT            : 587\n";
echo "MAIL_USERNAME        : 9a27c0001@smtp-brevo.com\n";
echo "MAIL_ENCRYPTION      : tls\n";
echo "BREVO_USERNAME       : 9a27c0001@smtp-brevo.com\n";
echo "BREVO_SMTP_KEY       : xsmtpsib-...\n";

echo "\n🔍 DIAGNOSTIC:\n";
echo "-------------------------------------------\n";

$mailMailer = getenv('MAIL_MAILER');
if ($mailMailer === 'brevo') {
    echo "✅ MAIL_MAILER = brevo (CORRECT)\n";
} elseif ($mailMailer === 'smtp') {
    echo "❌ MAIL_MAILER = smtp (ERREUR!)\n";
    echo "   → Changez MAIL_MAILER en 'brevo' sur Render\n";
} else {
    echo "❌ MAIL_MAILER = " . ($mailMailer ?: 'NON DÉFINI') . " (ERREUR!)\n";
    echo "   → Définissez MAIL_MAILER='brevo' sur Render\n";
}

$mailHost = getenv('MAIL_HOST');
if ($mailHost === 'smtp-relay.brevo.com') {
    echo "✅ MAIL_HOST correct\n";
} else {
    echo "❌ MAIL_HOST = " . ($mailHost ?: 'NON DÉFINI') . "\n";
}

$mailPort = getenv('MAIL_PORT');
if ($mailPort === '587') {
    echo "✅ MAIL_PORT correct\n";
} else {
    echo "⚠️  MAIL_PORT = " . ($mailPort ?: 'NON DÉFINI') . "\n";
}

$brevoUsername = getenv('BREVO_USERNAME');
if ($brevoUsername) {
    echo "✅ BREVO_USERNAME défini\n";
} else {
    echo "❌ BREVO_USERNAME non défini\n";
}

$brevoKey = getenv('BREVO_SMTP_KEY');
if ($brevoKey && strlen($brevoKey) > 50) {
    echo "✅ BREVO_SMTP_KEY défini (longueur: " . strlen($brevoKey) . ")\n";
} else {
    echo "❌ BREVO_SMTP_KEY invalide ou trop court\n";
}

echo "\n📝 ACTIONS REQUISES SUR RENDER:\n";
echo "===========================================\n";
if ($mailMailer !== 'brevo') {
    echo "1. ⚠️  URGENT: Modifiez MAIL_MAILER\n";
    echo "   - Allez dans Environment sur Render\n";
    echo "   - Trouvez MAIL_MAILER\n";
    echo "   - Changez la valeur en: brevo\n";
    echo "   - Cliquez 'Save Changes'\n\n";
}

echo "2. Vérifiez que MAIL_USERNAME est bien défini\n";
echo "   MAIL_USERNAME=9a27c0001@smtp-brevo.com\n\n";

echo "3. Après modification, attendez le redéploiement\n";
echo "   et testez à nouveau la création de compte\n\n";

echo "===========================================\n";
echo "FIN DU DIAGNOSTIC\n";
echo "===========================================\n";
