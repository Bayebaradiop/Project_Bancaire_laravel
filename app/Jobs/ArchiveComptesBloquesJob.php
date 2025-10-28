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

class ArchiveComptesBloquesJob implements ShouldQueue
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
     * Archive les comptes dont la date de début de blocage est arrivée
     * et archive aussi toutes leurs transactions
     */
    public function handle(): void
    {
        Log::info('ArchiveComptesBloquesJob: Démarrage du job d\'archivage');

        // Récupérer les comptes avec blocage programmé dont la date est arrivée
        $comptesAArchiver = Compte::where('blocage_programme', true)
            ->where('dateDebutBlocage', '<=', Carbon::now())
            ->get();

        Log::info("ArchiveComptesBloquesJob: {$comptesAArchiver->count()} comptes à archiver");

        foreach ($comptesAArchiver as $compte) {
            try {
                DB::beginTransaction();

                // 1. Archiver les transactions du compte
                $transactionsArchivees = $this->archiverTransactions($compte);

                // 2. Archiver le compte dans Neon
                $this->archiverCompteDansNeon($compte, $transactionsArchivees);

                // 3. Activer le blocage dans PostgreSQL (Render)
                $compte->update([
                    'statut' => 'bloque',
                    'dateBlocage' => Carbon::now(),
                    'blocage_programme' => false
                ]);

                DB::commit();
                Log::info("ArchiveComptesBloquesJob: Compte #{$compte->numeroCompte} archivé avec {$transactionsArchivees} transactions");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("ArchiveComptesBloquesJob: Erreur archivage compte #{$compte->numeroCompte}: " . $e->getMessage());
            }
        }

        Log::info('ArchiveComptesBloquesJob: Job terminé');
    }

    /**
     * Archive les transactions d'un compte dans Neon
     *
     * @param Compte $compte
     * @return int Nombre de transactions archivées
     */
    private function archiverTransactions(Compte $compte): int
    {
        try {
            // Récupérer toutes les transactions du compte
            $transactions = DB::table('transactions')
                ->where('compte_id', $compte->id)
                ->orWhere('compte_emetteur_id', $compte->id)
                ->orWhere('compte_destinataire_id', $compte->id)
                ->get();

            if ($transactions->isEmpty()) {
                return 0;
            }

            // Connexion à Neon
            $neonConnection = DB::connection('neon');

            // Archiver chaque transaction
            foreach ($transactions as $transaction) {
                $neonConnection->table('transactions')->insert([
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'compte_id' => $transaction->compte_id,
                    'compte_emetteur_id' => $transaction->compte_emetteur_id ?? null,
                    'compte_destinataire_id' => $transaction->compte_destinataire_id ?? null,
                    'statut' => $transaction->statut,
                    'reference' => $transaction->reference ?? null,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                    'archived_at' => Carbon::now(),
                ]);
            }

            Log::info("ArchiveComptesBloquesJob: {$transactions->count()} transactions archivées pour compte #{$compte->numeroCompte}");
            return $transactions->count();
        } catch (\Exception $e) {
            Log::error("ArchiveComptesBloquesJob: Erreur archivage transactions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Archive un compte dans la base de données Neon
     *
     * @param Compte $compte
     * @param int $nombreTransactions
     */
    private function archiverCompteDansNeon(Compte $compte, int $nombreTransactions): void
    {
        try {
            // Connexion à Neon
            $neonConnection = DB::connection('neon');

            // Insérer le compte dans Neon
            $neonConnection->table('comptes')->insert([
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'solde' => $compte->solde,
                'type' => $compte->type,
                'statut' => 'bloque',
                'client_id' => $compte->client_id,
                'motifBlocage' => $compte->motifBlocage,
                'dateBlocage' => Carbon::now(),
                'dateDebutBlocage' => $compte->dateDebutBlocage,
                'dateDeblocagePrevue' => $compte->dateDeblocagePrevue,
                'created_at' => $compte->created_at,
                'updated_at' => Carbon::now(),
                'archived_at' => Carbon::now(),
                'metadata' => json_encode([
                    'transactions_count' => $nombreTransactions,
                    'archive_reason' => 'blocage_programme',
                    'archived_by_job' => true
                ])
            ]);

            Log::info("ArchiveComptesBloquesJob: Compte #{$compte->numeroCompte} archivé dans Neon");
        } catch (\Exception $e) {
            Log::error("ArchiveComptesBloquesJob: Erreur archivage Neon: " . $e->getMessage());
            throw $e;
        }
    }
}
