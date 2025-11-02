<?php

namespace App\Listeners;

use App\Events\CompteCreated;
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
        $compte = $event->compte;
        $client = $compte->client;
        $password = $event->password;
        $code = $event->code;

        // Envoi de l'email avec le mot de passe (NON BLOQUANT)
        if ($password) {
            $this->envoyerEmail($client, $compte, $password, $code);
        }

        // L'envoi du SMS Twilio a été supprimé

        Log::info("Notification email envoyée pour le compte #{$compte->numeroCompte}");
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

    // ...existing code...
}
