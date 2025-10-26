<?php

namespace App\Observers;

use App\Models\Compte;
use App\Services\CompteArchiveService;
use Illuminate\Support\Facades\Log;

class CompteObserver
{
    protected CompteArchiveService $archiveService;

    public function __construct(CompteArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
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
    }
}
