<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope pour filtrer automatiquement les comptes actifs non archivés.
 * 
 * Ce scope s'applique automatiquement à toutes les requêtes sur le model Compte
 * pour s'assurer qu'on ne récupère QUE les comptes :
 * - Non archivés (archived_at IS NULL)
 * - Avec statut 'actif'
 * - Non supprimés (soft delete automatique via SoftDeletes trait)
 * 
 * Pour désactiver ce scope sur une requête spécifique, utiliser :
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
        $builder->whereNull('archived_at')
                ->where('statut', 'actif');
    }
}
