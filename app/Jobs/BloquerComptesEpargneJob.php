<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Services\CompteArchiveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job pour bloquer automatiquement les comptes épargne
 * dont la date de début de blocage est arrivée
 * et les archiver dans Neon
 */
class BloquerComptesEpargneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CompteArchiveService $compteArchiveService): void
    {
        try {
            Log::info('Début du blocage automatique des comptes programmés');

            // Récupérer tous les comptes avec blocage programmé dont la date est arrivée
            $comptes = Compte::where('statut', 'actif')
                ->where('blocage_programme', true)
                ->whereNotNull('dateDebutBlocage')
                ->whereDate('dateDebutBlocage', '<=', now())
                ->get();

            $comptesBloques = 0;

            foreach ($comptes as $compte) {
                try {
                    DB::beginTransaction();

                    // 1. Mettre à jour le statut à bloqué
                    $compte->update([
                        'statut' => 'bloque',
                        'dateBlocage' => now(),
                        'blocage_programme' => false,
                        'derniereModification' => now(),
                        'version' => $compte->version + 1,
                    ]);

                    // 2. Archiver dans Neon
                    $compteArchiveService->archiveCompte(
                        $compte,
                        null,
                        $compte->motifBlocage ?? 'Blocage automatique programmé'
                    );

                    // 3. Supprimer de PostgreSQL (soft delete)
                    $compte->delete();

                    $comptesBloques++;

                    Log::info('Compte épargne bloqué automatiquement et archivé', [
                        'compte_id' => $compte->id,
                        'numeroCompte' => $compte->numeroCompte,
                        'dateDebutBlocage' => $compte->dateDebutBlocage,
                        'motifBlocage' => $compte->motifBlocage,
                    ]);

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erreur lors du blocage automatique du compte', [
                        'compte_id' => $compte->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            Log::info('Blocage automatique des comptes terminé', [
                'comptes_bloques' => $comptesBloques,
                'total_comptes_a_bloquer' => $comptes->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur critique dans BloquerComptesEpargneJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('BloquerComptesEpargneJob a échoué après plusieurs tentatives', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
