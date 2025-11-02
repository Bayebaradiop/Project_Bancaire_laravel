<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * The table/collection associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = '_id';

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'numeroTransaction',
        'compte_id',
        'compte_source_id',
        'compte_destinataire_id',
        'type',
        'montant',
        'devise',
        'frais',
        'statut',
        'description',
        'transaction_parent_id',
        'date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec le compte principal.
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    /**
     * Relation avec le compte source (pour transferts).
     */
    public function compteSource(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_source_id');
    }

    /**
     * Relation avec le compte destinataire (pour dépôts et transferts).
     */
    public function compteDestinataire(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_destinataire_id');
    }

    /**
     * Relation avec la transaction parent (pour annulations).
     */
    public function transactionParent(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_parent_id');
    }

    /**
     * Scope pour filtrer par type de transaction.
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par compte (source, destination ou principal).
     *
     * @param Builder $query
     * @param int $compteId
     * @return Builder
     */
    public function scopeByCompte(Builder $query, int $compteId): Builder
    {
        return $query->where(function($q) use ($compteId) {
            $q->where('compte_id', $compteId)
              ->orWhere('compte_source_id', $compteId)
              ->orWhere('compte_destinataire_id', $compteId);
        });
    }

    /**
     * Scope pour filtrer par plage de dates.
     *
     * @param Builder $query
     * @param string $dateDebut
     * @param string|null $dateFin
     * @return Builder
     */
    public function scopeByDateRange(Builder $query, string $dateDebut, ?string $dateFin = null): Builder
    {
        $query->whereDate('created_at', '>=', $dateDebut);
        
        if ($dateFin) {
            $query->whereDate('created_at', '<=', $dateFin);
        }
        
        return $query;
    }

    /**
     * Scope pour filtrer les transactions effectuées (validées).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeEffectuee(Builder $query): Builder
    {
        return $query->where('statut', 'validee');
    }

    /**
     * Scope pour filtrer les transactions en attente.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope pour filtrer les transactions annulées.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAnnulee(Builder $query): Builder
    {
        return $query->where('statut', 'annulee');
    }

    /**
     * Vérifier si la transaction peut être annulée.
     *
     * @return bool
     */
    public function peutEtreAnnulee(): bool
    {
        // Seulement les transactions validées de moins de 24h
        return $this->statut === 'validee' 
            && $this->created_at->diffInHours(now()) <= 24
            && in_array($this->type, ['depot', 'retrait', 'transfert']);
    }
}
