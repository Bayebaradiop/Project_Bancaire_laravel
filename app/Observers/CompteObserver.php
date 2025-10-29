<?php

namespace App\Observers;

use App\Models\Compte;
use App\Services\CompteArchiveService;
use App\Services\NumeroCompteService;
use App\Mail\WelcomeClientMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CompteObserver
{
    protected CompteArchiveService $archiveService;
    protected NumeroCompteService $numeroService;

    public function __construct(
        CompteArchiveService $archiveService,
        NumeroCompteService $numeroService
    ) {
        $this->archiveService = $archiveService;
        $this->numeroService = $numeroService;
    }

    /**
     * Événement AVANT la création d'un compte
     * Générer le numéro de compte si absent
     */
    public function creating(Compte $compte)
    {
        if (empty($compte->numeroCompte)) {
            $compte->numeroCompte = $this->numeroService->generer();
            
            Log::info('Numéro de compte généré', [
                'numeroCompte' => $compte->numeroCompte,
            ]);
        }
    }

    /**
     * Surveiller les changements de statut pour archiver automatiquement
     * les comptes fermés et bloqués
     */
    public function updated(Compte $compte)
    {
        // Vérifier si le statut a changé
        if ($compte->isDirty('statut')) {
            $nouveauStatut = $compte->statut;
            $ancienStatut = $compte->getOriginal('statut');

            Log::info('Changement de statut détecté', [
                'compte' => $compte->numeroCompte,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
            ]);

            // Si le compte passe à "fermé" ou "bloqué", l'archiver automatiquement
            if (in_array($nouveauStatut, ['ferme', 'bloque']) && !$compte->archived_at) {
                try {
                    Log::info('Archivage automatique du compte', [
                        'compte' => $compte->numeroCompte,
                        'statut' => $nouveauStatut,
                    ]);

                    $reason = $nouveauStatut === 'ferme' 
                        ? 'Archivage automatique : compte fermé' 
                        : 'Archivage automatique : compte bloqué';

                    $this->archiveService->archiveCompte($compte, null, $reason);

                    Log::info('Compte archivé avec succès', [
                        'compte' => $compte->numeroCompte,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'archivage automatique', [
                        'compte' => $compte->numeroCompte,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Lors de la création d'un compte, vérifier si il doit être archivé
     * et envoyer l'email de bienvenue si un nouveau client a été créé
     */
    public function created(Compte $compte)
    {
        // Si le compte est créé directement avec statut fermé ou bloqué, l'archiver
        if (in_array($compte->statut, ['ferme', 'bloque'])) {
            try {
                $reason = $compte->statut === 'ferme' 
                    ? 'Archivage automatique : compte créé fermé' 
                    : 'Archivage automatique : compte créé bloqué';

                $this->archiveService->archiveCompte($compte, null, $reason);

                Log::info('Compte créé et archivé automatiquement', [
                    'compte' => $compte->numeroCompte,
                    'statut' => $compte->statut,
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'archivage automatique à la création', [
                    'compte' => $compte->numeroCompte,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Envoyer l'email de bienvenue si un nouveau client a été créé
        $password = session('temp_client_password');
        $code = session('temp_client_code');

        if ($password && $code) {
            try {
                $compte->load('client.user');
                
                if ($compte->client && $compte->client->user) {
                    Mail::to($compte->client->user->email)->send(
                        new WelcomeClientMail(
                            $compte->client->user->nomComplet,
                            $compte->client->user->email,
                            $password,
                            $code,
                            $compte->numeroCompte
                        )
                    );

                    Log::info('Email de bienvenue envoyé', [
                        'compte' => $compte->numeroCompte,
                        'email' => $compte->client->user->email,
                    ]);
                }

                // Nettoyer la session
                session()->forget(['temp_client_password', 'temp_client_code']);

            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'envoi de l\'email de bienvenue', [
                    'compte' => $compte->numeroCompte,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
