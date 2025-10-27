<?php

namespace App\Repositories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CompteRepository
{
    protected Compte $model;

    public function __construct(Compte $model)
    {
        $this->model = $model;
    }

    /**
     * Récupérer tous les comptes actifs avec pagination et filtres
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllActive(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->with('client.user')
            ->active()
            ->whereNull('deleted_at');

        // Appliquer les filtres
        if (isset($filters['type'])) {
            $query->type($filters['type']);
        }

        if (isset($filters['statut'])) {
            $query->statut($filters['statut']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Appliquer le tri
        $sortBy = $filters['sort_by'] ?? 'dateCreation';
        $order = $filters['order'] ?? 'desc';
        $query->sortBy($sortBy, $order);

        return $query->paginate($perPage);
    }

    /**
     * Trouver un compte par son ID
     *
     * @param string $id
     * @return Compte|null
     */
    public function findById(string $id): ?Compte
    {
        return $this->model->with('client.user')->find($id);
    }

    /**
     * Trouver un compte par son numéro
     *
     * @param string $numero
     * @return Compte|null
     */
    public function findByNumero(string $numero): ?Compte
    {
        return $this->model->with('client.user')->numero($numero)->first();
    }

    /**
     * Créer un nouveau compte
     *
     * @param array $data
     * @return Compte
     */
    public function create(array $data): Compte
    {
        return $this->model->create($data);
    }

    /**
     * Mettre à jour un compte
     *
     * @param Compte $compte
     * @param array $data
     * @return bool
     */
    public function update(Compte $compte, array $data): bool
    {
        return $compte->update($data);
    }

    /**
     * Supprimer un compte (soft delete) et l'archiver dans Neon
     *
     * @param Compte $compte
     * @return bool
     */
    public function deleteAndArchive(Compte $compte): bool
    {
        DB::transaction(function () use ($compte) {
            // Calculer le solde actuel
            $solde = $compte->solde;

            // Archiver dans Neon
            DB::connection('neon')->table('archives_comptes')->insert([
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'type' => $compte->type,
                'solde' => $solde,
                'statut' => 'ferme',
                'dateFermeture' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Soft delete dans la base principale
            $compte->update([
                'statut' => 'ferme',
                'deleted_at' => now(),
            ]);
        });

        return true;
    }

    /**
     * Restaurer un compte supprimé
     *
     * @param string $id
     * @return Compte|null
     */
    public function restore(string $id): ?Compte
    {
        $compte = $this->model->withTrashed()->find($id);

        if ($compte && $compte->trashed()) {
            DB::transaction(function () use ($compte) {
                // Supprimer de l'archive Neon
                DB::connection('neon')->table('archives_comptes')
                    ->where('id', $compte->id)
                    ->delete();

                // Restaurer dans la base principale
                $compte->update([
                    'statut' => 'actif',
                    'deleted_at' => null,
                ]);
            });

            return $compte->fresh();
        }

        return null;
    }

    /**
     * Récupérer les comptes archivés depuis Neon
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getArchived(int $perPage = 10): LengthAwarePaginator
    {
        return DB::connection('neon')
            ->table('archives_comptes')
            ->orderBy('dateFermeture', 'desc')
            ->paginate($perPage);
    }

    /**
     * Vérifier si un compte existe dans l'archive
     *
     * @param string $id
     * @return bool
     */
    public function existsInArchive(string $id): bool
    {
        return DB::connection('neon')
            ->table('archives_comptes')
            ->where('id', $id)
            ->exists();
    }
}