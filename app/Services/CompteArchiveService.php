<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\CompteArchive;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des archives de comptes dans Neon (cloud)
 * 
 * Ce service gère le transfert des comptes épargne vers la base Neon
 * pour archivage long terme dans le cloud
 */
class CompteArchiveService
{
    /**
     * Archiver un compte vers Neon (cloud)
     * 
     * @param Compte $compte Le compte à archiver
     * @param User|null $user L'utilisateur qui effectue l'archivage (nullable)
     * @param string|null $reason La raison de l'archivage
     * @return CompteArchive
     * @throws \Exception
     */
    public function archiveCompte(Compte $compte, ?User $user = null, ?string $reason = null): CompteArchive
    {
        try {
            DB::beginTransaction();
            
            // 1. Vérifier que le compte peut être archivé (fermé, bloqué, ou épargne)
            // Note : Tous les comptes fermés/bloqués sont archivés automatiquement
            
            // 2. Charger les relations nécessaires
            $compte->load('client.user');
            
            // 3. Créer l'archive dans Neon
            $archive = CompteArchive::create([
                'id' => $compte->id,
                'numerocompte' => $compte->numeroCompte,  // Mapping vers lowercase
                'client_id' => $compte->client_id,
                'type' => $compte->type,
                'solde' => 0, // Solde par défaut
                'devise' => $compte->devise,
                'statut' => $compte->statut,
                'motifblocage' => $compte->motifBlocage,  // Mapping vers lowercase
                'metadata' => null,
                'archived_at' => now(),
                'archived_by' => $user?->id,  // Nullable
                'archive_reason' => $reason ?? 'Archivage automatique',
                
                // Données client dénormalisées
                'client_nom' => $compte->client->user->nomComplet ?? null,
                'client_email' => $compte->client->user->email ?? null,
                'client_telephone' => $compte->client->user->telephone ?? null,
            ]);
            
            // 4. Marquer le compte comme archivé dans la base principale
            $compte->update([
                'archived_at' => now(),
                'cloud_storage_path' => "neon://comptes_archives/{$compte->id}",
            ]);
            
            DB::commit();
            
            Log::info('Compte archivé vers Neon', [
                'compte_id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'archived_by' => $user?->email ?? 'système',
                'archive_reason' => $reason,
            ]);
            
            return $archive;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de l\'archivage du compte', [
                'compte_id' => $compte->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Récupérer les comptes archivés d'un client depuis Neon
     * 
     * @param string $clientId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getArchivedComptes(string $clientId)
    {
        return CompteArchive::forClient($clientId)
            ->orderBy('archived_at', 'desc')
            ->get();
    }

    /**
     * Récupérer tous les comptes archivés depuis Neon (pour les admins)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllArchivedComptes()
    {
        return CompteArchive::orderBy('archived_at', 'desc')
            ->get();
    }
    
    /**
     * Récupérer un compte archivé spécifique depuis Neon
     * 
     * @param string $numeroCompte
     * @return CompteArchive|null
     */
    public function getArchivedCompte(string $numeroCompte): ?CompteArchive
    {
        return CompteArchive::where('numerocompte', $numeroCompte)->first();
    }
    
    /**
     * Restaurer un compte depuis l'archive Neon
     * 
     * @param string $compteId
     * @param User $user
     * @return Compte
     * @throws \Exception
     */
    public function restoreCompte(string $compteId, User $user): Compte
    {
        try {
            DB::beginTransaction();
            
            // 1. Récupérer l'archive depuis Neon
            $archive = CompteArchive::findOrFail($compteId);
            
            // 2. Récupérer le compte dans la base principale
            $compte = Compte::withTrashed()->findOrFail($compteId);
            
            // 3. Restaurer le compte
            $compte->update([
                'archived_at' => null,
                'cloud_storage_path' => null,
            ]);
            
            // 4. Optionnel: Supprimer l'archive de Neon
            // $archive->delete();
            
            DB::commit();
            
            Log::info('Compte restauré depuis Neon', [
                'compte_id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'restored_by' => $user->email,
            ]);
            
            return $compte;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la restauration du compte', [
                'compte_id' => $compteId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Archiver automatiquement les comptes épargne inactifs depuis X mois
     * 
     * @param User $user Utilisateur système qui effectue l'archivage
     * @param int $months Nombre de mois d'inactivité (par défaut 12)
     * @return int Nombre de comptes archivés
     */
    public function archiveInactiveComptes(User $user, int $months = 12): int
    {
        $count = 0;
        $inactiveDate = now()->subMonths($months);
        
        $comptesInactifs = Compte::where('type', 'epargne')
            ->where('statut', 'actif')
            ->whereNull('archived_at')
            ->where('updated_at', '<', $inactiveDate)
            ->get();
        
        foreach ($comptesInactifs as $compte) {
            try {
                $this->archiveCompte(
                    $compte, 
                    $user, 
                    "Inactif depuis plus de {$months} mois"
                );
                $count++;
            } catch (\Exception $e) {
                Log::warning("Impossible d'archiver le compte {$compte->numeroCompte}: " . $e->getMessage());
            }
        }
        
        Log::info("Archivage automatique terminé", [
            'comptes_archives' => $count,
            'inactivite_mois' => $months,
        ]);
        
        return $count;
    }
}
