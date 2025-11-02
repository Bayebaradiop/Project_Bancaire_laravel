<?php

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * Créer une nouvelle transaction.
     *
     * @param array $data
     * @return Transaction
     */
    public function create(array $data): Transaction;

    /**
     * Trouver une transaction par son ID.
     *
     * @param string $id
     * @return Transaction|null
     */
    public function find(string $id): ?Transaction;

    /**
     * Trouver une transaction par son numéro.
     *
     * @param string $numeroTransaction
     * @return Transaction|null
     */
    public function findByNumero(string $numeroTransaction): ?Transaction;

    /**
     * Mettre à jour une transaction.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     */
    public function update(Transaction $transaction, array $data): Transaction;

    /**
     * Filtrer les transactions avec pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function filter(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtenir les transactions d'un compte.
     *
     * @param string $numeroCompte
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCompte(string $numeroCompte, int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtenir les transactions d'un client.
     *
     * @param int $clientId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByClient(int $clientId, int $perPage = 15): LengthAwarePaginator;
}
