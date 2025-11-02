<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Services\BrevoApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class SendClientNotification
{
    /**
     * Handle the event.
     * Les envois Email sont non-bloquants et envoyés immédiatement (pas de queue)
     * Si l'envoi échoue, la création du compte continue
     */
    public function handle(CompteCreated $event)
    {
        try {
            $compte = $event->compte;
            $client = $compte->client;
            $password = $event->password;
            $code = $event->code;

            Log::info("🔔 Événement CompteCreated reçu", [
                'compte' => $compte->numeroCompte,
                'client_email' => $client->email ?? 'N/A',
                'has_password' => !empty($password),
                'has_code' => !empty($code),
            ]);

            // Envoi de l'email avec le mot de passe (NON BLOQUANT)
            if ($password) {
                $this->envoyerEmail($client, $compte, $password, $code);
            } else {
                Log::warning("⚠️ Pas de mot de passe fourni, email non envoyé", [
                    'compte' => $compte->numeroCompte,
                ]);
            }

            Log::info("✅ Notification terminée pour le compte #{$compte->numeroCompte}");
            
        } catch (\Exception $e) {
            Log::error("❌ Erreur dans handle() SendClientNotification", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Envoi de l'email avec le mot de passe
     * Si l'envoi échoue, on log l'erreur mais on ne bloque pas
     */
    private function envoyerEmail($client, $compte, $password, $code = null): void
    {
        try {
            $email = $client->email ?? null;
            
            if (!$email) {
                Log::warning("Pas d'email pour le client #{$client->id}");
                return;
            }

            // Utiliser l'API Brevo si BREVO_API_KEY est défini, sinon utiliser SMTP
            if (env('BREVO_API_KEY')) {
                Log::info("📧 Utilisation de l'API Brevo pour l'envoi");
                
                // Générer le contenu HTML de l'email
                $htmlContent = view('emails.compte-created', [
                    'compte' => $compte,
                    'password' => $password,
                    'code' => $code
                ])->render();

                $brevoService = new BrevoApiService();
                $result = $brevoService->sendEmail(
                    $email,
                    'Bienvenue sur Faysany Banque - Votre compte a été créé',
                    $htmlContent
                );

                if ($result['success']) {
                    Log::info("✅ Email envoyé avec succès via API Brevo", [
                        'destinataire' => $email,
                        'compte' => $compte->numeroCompte,
                        'messageId' => $result['messageId']
                    ]);
                } else {
                    throw new \Exception($result['error']);
                }
            } else {
                Log::info("📧 Utilisation de SMTP pour l'envoi");
                // Envoi via SMTP (méthode traditionnelle)
                Mail::to($email)->send(new \App\Mail\CompteCreatedMail($compte, $password, $code));
                
                Log::info("✅ Email envoyé avec succès via SMTP", [
                    'destinataire' => $email,
                    'compte' => $compte->numeroCompte,
                ]);
            }
            
        } catch (\Exception $e) {
            // Ne pas bloquer la création si l'email échoue
            Log::error("Erreur envoi email (non bloquant): " . $e->getMessage(), [
                'client_id' => $client->id,
                'compte' => $compte->numeroCompte,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // ...existing code...
}
