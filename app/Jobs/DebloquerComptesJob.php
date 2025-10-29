<?php

namespace App\Jobs;

use App\Models\CompteArchive;
use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job pour débloquer automatiquement les comptes dont la date de fin de blocage est arrivée
 * et les ramener de Neon vers PostgreSQL
 */
class DebloquerComptesJob implements ShouldQueue
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
    public function handle(): void
    {
        try {
            Log::info('Début du déblocage automatique des comptes');

            // Récupérer tous les comptes bloqués dans Neon avec date de fin de blocage arrivée
            $comptesADebloquer = CompteArchive::where('statut', 'bloque')
                ->whereNotNull('dateFinBlocage')
                ->whereDate('dateFinBlocage', '<=', now())
                ->get();

            $comptesDebloques = 0;

            foreach ($comptesADebloquer as $compteArchive) {
                try {
                    DB::beginTransaction();

                    // 1. Récupérer ou restaurer le compte dans PostgreSQL
                    $compte = Compte::withTrashed()->find($compteArchive->id);

                    if ($compte) {
                        // Le compte existe (soft deleted), le restaurer
                        $compte->restore();

                        // 2. Mettre à jour le statut à actif
                        $compte->update([
                            'statut' => 'actif',
                            'motifBlocage' => null,
                            'dateDebutBlocage' => null,
                            'dateFinBlocage' => null,
                            'dateBlocage' => null,
                            'blocage_programme' => false,
                            'archived_at' => null,
                            'cloud_storage_path' => null,
                            'derniereModification' => now(),
                            'version' => $compte->version + 1,
                        ]);

                        // 3. Supprimer de Neon
                        $compteArchive->delete();

                        $comptesDebloques++;

                        Log::info('Compte débloqué et restauré depuis Neon', [
                            'compte_id' => $compte->id,
                            'numeroCompte' => $compte->numeroCompte,
                            'dateFinBlocage' => $compteArchive->dateFinBlocage,
                        ]);

                        DB::commit();

                    } else {
                        // Le compte n'existe pas dans PostgreSQL, le recréer depuis l'archive
                        Log::warning('Compte archivé introuvable dans PostgreSQL', [
                            'compte_id' => $compteArchive->id,
                        ]);
                        DB::rollBack();
                    }

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erreur lors du déblocage automatique du compte', [
                        'compte_id' => $compteArchive->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            Log::info('Déblocage automatique des comptes terminé', [
                'comptes_debloques' => $comptesDebloques,
                'total_comptes_a_debloquer' => $comptesADebloquer->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur critique dans DebloquerComptesJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('DebloquerComptesJob a échoué après plusieurs tentatives', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
