<?php

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuration SMTP
$mail = new PHPMailer(true);

try {
    // Configuration du serveur SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'bayebara2000@gmail.com';
    $mail->Password = 'cfvoaitjqqnofxwz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Destinataires
    $mail->setFrom('bayebara2000@gmail.com', 'Faysany Banque');
    $mail->addAddress('bayebara2000@gmail.com', 'Test Recipient');

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test Email SMTP Direct - Production';
    $mail->Body = '<h1>Test SMTP Direct</h1><p>Cette email a été envoyé directement via PHPMailer pour tester la configuration SMTP en production.</p>';
    $mail->AltBody = 'Test SMTP Direct - Cette email a été envoyé directement via PHPMailer pour tester la configuration SMTP en production.';

    $mail->send();
    echo "✅ Email envoyé avec succès via SMTP direct!\n";
} catch (Exception $e) {
    echo "❌ Erreur lors de l'envoi: {$mail->ErrorInfo}\n";
}
