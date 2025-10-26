<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Modèle pour les comptes archivés stockés dans Neon (cloud)
 * 
 * Ce modèle utilise la connexion 'neon' configurée dans config/database.php
 * Les comptes épargne archivés sont automatiquement transférés vers Neon
 */
class CompteArchive extends Model
{
    use HasFactory, HasUuids;

    /**
     * La connexion à utiliser pour ce modèle (Neon cloud database)
     */
    protected $connection = 'neon';

    /**
     * Le nom de la table
     */
    protected $table = 'comptes_archives';

    /**
     * Les attributs qui peuvent être assignés en masse
     */
    protected $fillable = [
        'id',
        'numerocompte',  // Minuscule car PostgreSQL convertit en lowercase
        'client_id',
        'type',
        'solde',
        'devise',
        'statut',
        'motifblocage',  // Minuscule
        'metadata',
        'archived_at',
        'archived_by',
        'archive_reason',
        'client_nom',
        'client_email',
        'client_telephone',
    ];

    /**
     * Les attributs qui doivent être castés
     */
    protected $casts = [
        'metadata' => 'array',
        'archived_at' => 'datetime',
        'solde' => 'decimal:2',
    ];

    /**
     * Relation avec le client
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Relation avec l'utilisateur qui a archivé
     */
    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Scope pour filtrer par type de compte
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par client
     */
    public function scopeForClient($query, string $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope pour les comptes épargne uniquement
     */
    public function scopeEpargne($query)
    {
        return $query->where('type', 'epargne');
    }
}
