<?php

namespace App\Jobs;

use App\Models\Compte;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivateDeblocageScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * Débloque automatiquement les comptes dont la date de déblocage est arrivée
     * et restaure les comptes depuis la base Neon
     */
    public function handle(): void
    {
        // Récupérer tous les comptes bloqués dont la date de déblocage est arrivée
        $comptesADebloquer = Compte::where('statut', 'bloque')
            ->whereNotNull('dateDeblocagePrevue')
            ->where('dateDeblocagePrevue', '<=', Carbon::now())
            ->get();

        foreach ($comptesADebloquer as $compte) {
            try {
                DB::beginTransaction();

                // 1. Débloquer le compte
                $compte->update([
                    'statut' => 'actif',
                    'dateDeblocage' => Carbon::now(),
                    'motifBlocage' => null,
                    'dateBlocage' => null,
                    'dateDebutBlocage' => null,
                    'dateDeblocagePrevue' => null,
                    'blocage_programme' => false
                ]);

                // 2. Supprimer l'archive dans Neon (si elle existe)
                try {
                    $this->supprimerDeNeon($compte->id);
                } catch (\Exception $e) {
                    Log::warning("Impossible de supprimer de Neon: " . $e->getMessage());
                }

                DB::commit();
                Log::info("Compte #{$compte->numeroCompte} débloqué automatiquement et supprimé de Neon");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erreur lors du déblocage automatique du compte #{$compte->numeroCompte}: " . $e->getMessage());
            }
        }
    }

    /**
     * Supprime un compte archivé de la base de données Neon
     */
    private function supprimerDeNeon(string $compteId): void
    {
        // Connexion à Neon
        $neonConnection = DB::connection('neon');

        // Supprimer le compte de Neon
        $neonConnection->table('comptes')->where('id', $compteId)->delete();

        Log::info("Compte #{$compteId} supprimé de Neon");
    }
}
