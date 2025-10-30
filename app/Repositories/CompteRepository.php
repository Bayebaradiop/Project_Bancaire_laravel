<?php

namespace App\Repositories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            // Archiver dans Neon avec toutes les informations nécessaires
            DB::connection('neon')->table('archives_comptes')->insert([
                'id' => $compte->id,
                'numerocompte' => $compte->numeroCompte,
                'client_id' => $compte->client_id,
                'type' => $compte->type,
                'solde' => $solde,
                'devise' => $compte->devise ?? 'FCFA',
                'statut' => 'ferme',
                'archived_at' => now(),
                'archive_reason' => 'Suppression à la demande du client',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mettre le statut à 'ferme' avant le soft delete
            $compte->update(['statut' => 'ferme']);
            
            // Soft delete dans la base principale (utilise SoftDeletes)
            $compte->delete();
        });

        return true;
    }

    /**
     * Restaurer un compte supprimé depuis Neon
     *
     * @param string $id
     * @return Compte|null
     */
    public function restore(string $id): ?Compte
    {
        // Récupérer le compte depuis l'archive Neon
        $archivedCompte = DB::connection('neon')
            ->table('archives_comptes')
            ->where('id', $id)
            ->first();

        if (!$archivedCompte) {
            Log::error("Compte non trouvé dans Neon : {$id}");
            return null;
        }

        Log::info("Restauration du compte depuis Neon", [
            'id' => $id,
            'numerocompte' => $archivedCompte->numerocompte,
            'client_id' => $archivedCompte->client_id
        ]);

        return DB::transaction(function () use ($archivedCompte) {
            // Vérifier si le compte existe déjà dans PostgreSQL
            // IMPORTANT: Désactiver TOUS les scopes (ActiveCompteScope + SoftDeletes)
            $existingCompte = $this->model
                ->withoutGlobalScopes()
                ->withTrashed()
                ->find($archivedCompte->id);
            
            if ($existingCompte) {
                Log::info("Compte existe déjà dans PostgreSQL, restauration simple", [
                    'id' => $existingCompte->id,
                    'statut_avant' => $existingCompte->statut,
                    'deleted_at' => $existingCompte->deleted_at
                ]);
                
                // Si le compte existe déjà, on le restaure simplement
                if ($existingCompte->trashed()) {
                    $existingCompte->restore(); // Enlève deleted_at
                }
                
                // Réactiver le compte
                $existingCompte->statut = 'actif';
                $existingCompte->archived_at = null;
                $existingCompte->save();
                
                // Supprimer de l'archive Neon
                DB::connection('neon')->table('archives_comptes')
                    ->where('id', $archivedCompte->id)
                    ->delete();
                
                Log::info("Compte restauré avec succès depuis PostgreSQL");
                
                return $existingCompte->fresh(['client.user']);
            }
            
            Log::info("Compte n'existe pas dans PostgreSQL, création depuis Neon");
            
            // Si le compte n'existe pas, le recréer avec toutes les données
            $compteData = [
                'id' => $archivedCompte->id,
                'numeroCompte' => $archivedCompte->numerocompte,
                'type' => $archivedCompte->type,
                'client_id' => $archivedCompte->client_id,
                'solde' => $archivedCompte->solde ?? 0,
                'statut' => 'actif', // Réactivé
                'devise' => $archivedCompte->devise ?? 'FCFA',
                'archived_at' => null,
                'created_at' => $archivedCompte->created_at,
                'updated_at' => now(),
            ];
            
            Log::info("Données du compte à créer", $compteData);
            
            try {
                $compte = $this->model->create($compteData);
                
                // Supprimer de l'archive Neon
                DB::connection('neon')->table('archives_comptes')
                    ->where('id', $archivedCompte->id)
                    ->delete();
                
                Log::info("Compte restauré avec succès depuis Neon : {$compte->id}");
                
                return $compte->fresh(['client.user']);
                
            } catch (\Exception $e) {
                Log::error("Erreur lors de la création du compte", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Récupérer les comptes archivés depuis Neon
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getArchived(int $perPage = 10): LengthAwarePaginator
    {
        // Forcer la reconnexion pour éviter les problèmes de cache de plan
        DB::connection('neon')->reconnect();
        
        return DB::connection('neon')
            ->table('archives_comptes')
            ->select('id', 'numerocompte', 'client_id', 'type', 'solde', 'devise', 'statut', 'archived_at', 'created_at', 'updated_at')
            ->orderBy('archived_at', 'desc')
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

    /**
     * Récupérer un compte archivé par numéro de compte
     */
    public function getArchivedByNumero(string $numeroCompte): ?object
    {
        DB::connection('neon')->reconnect();
        
        return DB::connection('neon')
            ->table('archives_comptes')
            ->where('numerocompte', $numeroCompte)
            ->first();
    }
}