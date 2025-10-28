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

class DearchiveComptesBloquesJob implements ShouldQueue
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
     * Désarchive les comptes bloqués dont la date de fin de blocage est échue
     * et restaure aussi toutes leurs transactions
     */
    public function handle(): void
    {
        Log::info('DearchiveComptesBloquesJob: Démarrage du job de désarchivage');

        // Récupérer les comptes bloqués dont la date de déblocage est arrivée
        $comptesADebloquer = Compte::where('statut', 'bloque')
            ->whereNotNull('dateDeblocagePrevue')
            ->where('dateDeblocagePrevue', '<=', Carbon::now())
            ->get();

        Log::info("DearchiveComptesBloquesJob: {$comptesADebloquer->count()} comptes à débloquer");

        foreach ($comptesADebloquer as $compte) {
            try {
                DB::beginTransaction();

                // 1. Restaurer les transactions depuis Neon
                $transactionsRestaurees = $this->restaurerTransactions($compte);

                // 2. Débloquer le compte
                $compte->update([
                    'statut' => 'actif',
                    'dateDeblocage' => Carbon::now(),
                    'motifBlocage' => null,
                    'dateBlocage' => null,
                    'dateDebutBlocage' => null,
                    'dateDeblocagePrevue' => null,
                    'blocage_programme' => false
                ]);

                // 3. Supprimer l'archive dans Neon
                $this->supprimerArchivesNeon($compte);

                DB::commit();
                Log::info("DearchiveComptesBloquesJob: Compte #{$compte->numeroCompte} débloqué et désarchivé avec {$transactionsRestaurees} transactions");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("DearchiveComptesBloquesJob: Erreur déblocage compte #{$compte->numeroCompte}: " . $e->getMessage());
            }
        }

        Log::info('DearchiveComptesBloquesJob: Job terminé');
    }

    /**
     * Restaure les transactions d'un compte depuis Neon vers PostgreSQL
     *
     * @param Compte $compte
     * @return int Nombre de transactions restaurées
     */
    private function restaurerTransactions(Compte $compte): int
    {
        try {
            // Connexion à Neon
            $neonConnection = DB::connection('neon');

            // Récupérer les transactions archivées
            $transactionsArchivees = $neonConnection->table('transactions')
                ->where('compte_id', $compte->id)
                ->orWhere('compte_emetteur_id', $compte->id)
                ->orWhere('compte_destinataire_id', $compte->id)
                ->get();

            if ($transactionsArchivees->isEmpty()) {
                return 0;
            }

            // Restaurer dans PostgreSQL (si elles n'existent pas déjà)
            foreach ($transactionsArchivees as $transaction) {
                // Vérifier si la transaction existe déjà
                $exists = DB::table('transactions')->where('id', $transaction->id)->exists();
                
                if (!$exists) {
                    DB::table('transactions')->insert([
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'montant' => $transaction->montant,
                        'compte_id' => $transaction->compte_id,
                        'compte_emetteur_id' => $transaction->compte_emetteur_id ?? null,
                        'compte_destinataire_id' => $transaction->compte_destinataire_id ?? null,
                        'statut' => $transaction->statut,
                        'reference' => $transaction->reference ?? null,
                        'created_at' => $transaction->created_at,
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }

            // Supprimer les transactions de Neon
            $neonConnection->table('transactions')
                ->where('compte_id', $compte->id)
                ->orWhere('compte_emetteur_id', $compte->id)
                ->orWhere('compte_destinataire_id', $compte->id)
                ->delete();

            Log::info("DearchiveComptesBloquesJob: {$transactionsArchivees->count()} transactions restaurées pour compte #{$compte->numeroCompte}");
            return $transactionsArchivees->count();
        } catch (\Exception $e) {
            Log::error("DearchiveComptesBloquesJob: Erreur restauration transactions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprime les archives d'un compte de la base de données Neon
     *
     * @param Compte $compte
     */
    private function supprimerArchivesNeon(Compte $compte): void
    {
        try {
            // Connexion à Neon
            $neonConnection = DB::connection('neon');

            // Supprimer le compte de Neon
            $neonConnection->table('comptes')->where('id', $compte->id)->delete();

            Log::info("DearchiveComptesBloquesJob: Archive compte #{$compte->numeroCompte} supprimée de Neon");
        } catch (\Exception $e) {
            Log::warning("DearchiveComptesBloquesJob: Impossible de supprimer les archives Neon: " . $e->getMessage());
            // On ne throw pas l'erreur car le compte est déjà débloqué
        }
    }
}
