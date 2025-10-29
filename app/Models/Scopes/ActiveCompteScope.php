<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope pour filtrer les comptes selon les règles métier US 2.0.
 * 
 * Règles de filtrage automatique :
 * - Comptes CHÈQUE : tous (actif, bloqué, fermé) NON archivés
 * - Comptes ÉPARGNE : ACTIFS uniquement NON archivés
 * - Tous : Non supprimés (soft delete via SoftDeletes trait)
 * 
 * Ce scope garantit que l'endpoint GET /api/v1/comptes retourne :
 * "Liste compte non supprimés type cheque ou compte Epargne Actif"
 * 
 * Pour désactiver ce scope sur une requête spécifique :
 * Compte::withoutGlobalScope(ActiveCompteScope::class)->get()
 */
class ActiveCompteScope implements Scope
{
    /**
     * Applique le scope à la requête Eloquent.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Filtrer les comptes non archivés
        $builder->whereNull('archived_at')
            ->where(function ($query) {
                // Comptes CHÈQUE : tous les statuts (actif, bloqué, fermé)
                $query->where('type', 'cheque')
                    // OU Comptes ÉPARGNE : ACTIFS uniquement
                    ->orWhere(function ($q) {
                        $q->where('type', 'epargne')
                          ->where('statut', 'actif');
                    });
            });
    }
}
