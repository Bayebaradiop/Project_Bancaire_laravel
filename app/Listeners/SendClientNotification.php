<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class SendClientNotification implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'default';

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Handle the event.
     * Les envois SMS et Email sont non-bloquants : si ils échouent, la création continue
     */
    public function handle(CompteCreated $event)
    {
        $compte = $event->compte;
        $client = $compte->client;
        $password = $event->password;
        $code = $event->code;

        // Envoi de l'email avec le mot de passe (NON BLOQUANT)
        if ($password) {
            $this->envoyerEmail($client, $compte, $password, $code);
        }

        // Envoi du SMS avec le code (NON BLOQUANT)
        if ($code) {
            $this->envoyerSMS($client, $code);
        }

        Log::info("Notifications envoyées pour le compte #{$compte->numeroCompte}");
    }

    /**
     * Envoi de l'email avec le mot de passe
     * Si l'envoi échoue, on log l'erreur mais on ne bloque pas
     */
    private function envoyerEmail($client, $compte, $password, $code = null): void
    {
        try {
            $email = $client->user->email ?? null;
            
            if (!$email) {
                Log::warning("Pas d'email pour le client #{$client->id}");
                return;
            }

            // Envoi réel de l'email
            Mail::to($email)->send(new \App\Mail\CompteCreatedMail($compte, $password, $code));
            
            Log::info("✅ Email envoyé avec succès", [
                'destinataire' => $email,
                'compte' => $compte->numeroCompte,
            ]);
            
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

    /**
     * Envoi du SMS avec Twilio
     * Si l'envoi échoue, on log l'erreur mais on ne bloque pas
     */
    private function envoyerSMS($client, $code): void
    {
        try {
            $telephone = $client->user->telephone ?? null;
            
            if (!$telephone) {
                Log::warning("Pas de téléphone pour le client #{$client->id}");
                return;
            }

            // Configuration Twilio (depuis .env)
            $twilioSid = env('TWILIO_ACCOUNT_SID');
            $twilioToken = env('TWILIO_AUTH_TOKEN');
            $twilioPhone = env('TWILIO_PHONE_NUMBER');

            if (!$twilioSid || !$twilioToken || !$twilioPhone) {
                Log::warning("Configuration Twilio manquante (simulation SMS)", [
                    'destinataire' => $telephone,
                    'code' => $code,
                ]);
                return;
            }

            // Envoi via Twilio
            $message = "Bienvenue ! Votre code de vérification est : {$code}. À utiliser lors de votre première connexion.";
            
            $response = Http::withBasicAuth($twilioSid, $twilioToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json", [
                    'To' => $telephone,
                    'From' => $twilioPhone,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("SMS envoyé avec succès via Twilio", [
                    'destinataire' => $telephone,
                    'sid' => $response->json('sid'),
                ]);
            } else {
                Log::error("Échec envoi SMS Twilio (non bloquant)", [
                    'destinataire' => $telephone,
                    'error' => $response->body(),
                ]);
            }
            
        } catch (\Exception $e) {
            // Ne pas bloquer la création si le SMS échoue
            Log::error("Erreur envoi SMS (non bloquant): " . $e->getMessage(), [
                'client_id' => $client->id,
            ]);
        }
    }
}
