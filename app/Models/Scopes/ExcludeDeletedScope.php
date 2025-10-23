<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope pour exclure les comptes supprimés (soft deleted)
 * Appliqué automatiquement à toutes les requêtes
 */
class ExcludeDeletedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Déjà géré par SoftDeletes trait
        // Ce scope est juste un exemple de comment créer un Global Scope personnalisé
    }
}
