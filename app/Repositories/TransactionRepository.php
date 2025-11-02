<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * Créer une nouvelle transaction.
     *
     * @param array $data
     * @return Transaction
     */
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    /**
     * Trouver une transaction par son ID.
     *
     * @param string $id
     * @return Transaction|null
     */
    public function find(string $id): ?Transaction
    {
        return Transaction::find($id);
    }

    /**
     * Trouver une transaction par son numéro.
     *
     * @param string $numeroTransaction
     * @return Transaction|null
     */
    public function findByNumero(string $numeroTransaction): ?Transaction
    {
        return Transaction::where('numeroTransaction', $numeroTransaction)->first();
    }

    /**
     * Mettre à jour une transaction.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }

    /**
     * Filtrer les transactions avec pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function filter(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Note: Relations cross-database (MongoDB -> PostgreSQL) désactivées temporairement
        $query = Transaction::query();

        // Filtrer par type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filtrer par statut
        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        // Filtrer par compte (source ou destination)
        if (!empty($filters['compte'])) {
            $compte = Compte::where('numeroCompte', $filters['compte'])->first();
            if ($compte) {
                $query->where(function($q) use ($compte) {
                    $q->where('compte_id', $compte->id)
                      ->orWhere('compte_source_id', $compte->id)
                      ->orWhere('compte_destinataire_id', $compte->id);
                });
            }
        }

        // Filtrer par plage de dates
        if (!empty($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        // Filtrer par montant minimum
        if (!empty($filters['montant_min'])) {
            $query->where('montant', '>=', $filters['montant_min']);
        }

        // Filtrer par montant maximum
        if (!empty($filters['montant_max'])) {
            $query->where('montant', '<=', $filters['montant_max']);
        }

        // Trier par date décroissante
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Obtenir les transactions d'un compte.
     *
     * @param string $numeroCompte
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCompte(string $numeroCompte, int $perPage = 15): LengthAwarePaginator
    {
        $compte = Compte::where('numeroCompte', $numeroCompte)->firstOrFail();

        return Transaction::query()
            ->with(['compte', 'compteSource', 'compteDestinataire'])
            ->where(function($q) use ($compte) {
                $q->where('compte_id', $compte->id)
                  ->orWhere('compte_source_id', $compte->id)
                  ->orWhere('compte_destinataire_id', $compte->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtenir les transactions d'un client.
     *
     * @param int $clientId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByClient(int $clientId, int $perPage = 15): LengthAwarePaginator
    {
        $comptesIds = Compte::where('client_id', $clientId)->pluck('id');

        return Transaction::query()
            ->with(['compte', 'compteSource', 'compteDestinataire'])
            ->where(function($q) use ($comptesIds) {
                $q->whereIn('compte_id', $comptesIds)
                  ->orWhereIn('compte_source_id', $comptesIds)
                  ->orWhereIn('compte_destinataire_id', $comptesIds);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
