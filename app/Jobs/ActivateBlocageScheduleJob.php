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

class ActivateBlocageScheduleJob implements ShouldQueue
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
     * Active les blocages programmés dont la date est arrivée
     * et archive les comptes dans la base Neon
     */
    public function handle(): void
    {
        // Récupérer tous les comptes avec blocage programmé dont la date est arrivée
        $comptesABloquer = Compte::where('blocage_programme', true)
            ->where('dateDebutBlocage', '<=', Carbon::now())
            ->get();

        foreach ($comptesABloquer as $compte) {
            try {
                DB::beginTransaction();

                // 1. Archiver le compte dans Neon (si la connexion existe)
                try {
                    $this->archiverDansNeon($compte);
                } catch (\Exception $e) {
                    Log::warning("Impossible d'archiver dans Neon: " . $e->getMessage());
                }

                // 2. Activer le blocage dans PostgreSQL (Render)
                $compte->update([
                    'statut' => 'bloque',
                    'dateBlocage' => Carbon::now(),
                    'blocage_programme' => false
                ]);

                DB::commit();
                Log::info("Compte #{$compte->numeroCompte} bloqué automatiquement et archivé dans Neon");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erreur lors de l'activation du blocage du compte #{$compte->numeroCompte}: " . $e->getMessage());
            }
        }
    }

    /**
     * Archive un compte dans la base de données Neon
     */
    private function archiverDansNeon(Compte $compte): void
    {
        // Connexion à Neon
        $neonConnection = DB::connection('neon');

        // Insérer le compte dans Neon
        $neonConnection->table('comptes')->insert([
            'id' => $compte->id,
            'numeroCompte' => $compte->numeroCompte,
            'solde' => $compte->solde,
            'type' => $compte->type,
            'statut' => 'bloque',
            'user_id' => $compte->user_id,
            'motifBlocage' => $compte->motifBlocage,
            'dateBlocage' => Carbon::now(),
            'dateDebutBlocage' => $compte->dateDebutBlocage,
            'dateDeblocagePrevue' => $compte->dateDeblocagePrevue,
            'created_at' => $compte->created_at,
            'updated_at' => Carbon::now()
        ]);

        Log::info("Compte #{$compte->numeroCompte} archivé dans Neon");
    }
}
