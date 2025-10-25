<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Mail\CompteCreatedMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClientNotification
{
    public function handle(CompteCreated $event)
    {
        $compte = $event->compte;
        $client = $compte->client;
        $password = $event->password;
        $code = $event->code;

        // Envoi de l'email avec le mot de passe
        try {
            // Envoi réel de l'email
            Mail::to($client->user->email)->send(new CompteCreatedMail($compte, $password));
            
            Log::info("Email envoyé", [
                'destinataire' => $client->user->email ?? 'N/A',
                'type' => 'Création de compte',
                'password' => $password,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email: " . $e->getMessage());
        }

        // Envoi du SMS avec le code
        try {
            // Simuler l'envoi de SMS (à remplacer par votre service SMS)
            Log::info("SMS envoyé", [
                'destinataire' => $client->user->telephone ?? 'N/A',
                'type' => 'Code de vérification',
                'code' => $code,
            ]);
            
            // TODO: Implémenter l'envoi réel de SMS
            // $this->sendSMS($client->user->telephone, "Votre code de vérification: {$code}");
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du SMS: " . $e->getMessage());
        }
    }

    // Méthode pour envoyer le SMS (à implémenter selon votre service)
    private function sendSMS($telephone, $message)
    {
        // Exemple avec une API SMS
        // Http::post('https://api-sms.com/send', [
        //     'to' => $telephone,
        //     'message' => $message,
        // ]);
    }
}
