<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\ActiveCompteScope;

class Compte extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comptes';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
        'numeroCompte',
        'client_id',
        'type',
        'devise',
        'statut',
        'motifBlocage',
        'version',
        'archived_at',
        'cloud_storage_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dateCreation' => 'datetime',
        'derniereModification' => 'datetime',
        'archived_at' => 'datetime',
        'version' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'devise' => 'FCFA',
        'statut' => 'actif',
        'version' => 1,
    ];

    /**
     * Disable Laravel's default timestamps and use custom ones.
     */
    const CREATED_AT = 'dateCreation';
    const UPDATED_AT = 'derniereModification';

    /**
     * The name of the "deleted at" column.
     *
     * @var string
     */
    const DELETED_AT = 'deleted_at';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Générer automatiquement le numéro de compte lors de la création
        static::creating(function ($compte) {
            if (empty($compte->numeroCompte)) {
                $compte->numeroCompte = self::generateNumeroCompte();
            }
        });

        // Incrémenter la version lors de la mise à jour (optimistic locking)
        static::updating(function ($compte) {
            $compte->version++;
        });
    }

    /**
     * Booted method pour enregistrer les Global Scopes.
     */
    protected static function booted(): void
    {
        // Appliquer le Global Scope pour filtrer automatiquement
        // les comptes actifs non archivés sur TOUTES les requêtes
        static::addGlobalScope(new ActiveCompteScope());
    }

    /**
     * Générer un numéro de compte unique.
     *
     * @return string
     */
    protected static function generateNumeroCompte(): string
    {
        do {
            // Format: CPXXXXXXXXXX (CP + 10 chiffres aléatoires)
            $numero = 'CP' . str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (self::where('numeroCompte', $numero)->exists());

        return $numero;
    }

    /**
     * Relation avec le client.
     *
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Vérifier si le compte est actif.
     *
     * @return bool
     */
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Vérifier si le compte est bloqué.
     *
     * @return bool
     */
    public function isBloque(): bool
    {
        return $this->statut === 'bloque';
    }

    /**
     * Vérifier si le compte est fermé.
     *
     * @return bool
     */
    public function isFerme(): bool
    {
        return $this->statut === 'ferme';
    }

    /**
     * Bloquer le compte.
     *
     * @param string $motif
     * @return void
     */
    public function bloquer(string $motif): void
    {
        $this->update([
            'statut' => 'bloque',
            'motifBlocage' => $motif,
        ]);
    }

    /**
     * Débloquer le compte.
     *
     * @return void
     */
    public function debloquer(): void
    {
        $this->update([
            'statut' => 'actif',
            'motifBlocage' => null,
        ]);
    }

    /**
     * Fermer le compte.
     *
     * @param string $motif
     * @return void
     */
    public function fermer(string $motif): void
    {
        $this->update([
            'statut' => 'ferme',
            'motifBlocage' => $motif,
        ]);
    }

    /**
     * Scope pour récupérer un compte par son numéro.
     *
     * @param Builder $query
     * @param string $numero
     * @return Builder
     */
    public function scopeNumero(Builder $query, string $numero): Builder
    {
        return $query->where('numeroCompte', $numero);
    }

    /**
     * Scope pour récupérer les comptes d'un client basé sur le téléphone.
     *
     * @param Builder $query
     * @param string $telephone
     * @return Builder
     */
    public function scopeClient(Builder $query, string $telephone): Builder
    {
        return $query->whereHas('client.user', function (Builder $q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

    /**
     * Scope pour filtrer par type.
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par statut.
     *
     * @param Builder $query
     * @param string $statut
     * @return Builder
     */
    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope pour filtrer par devise.
     *
     * @param Builder $query
     * @param string $devise
     * @return Builder
     */
    public function scopeDevise(Builder $query, string $devise): Builder
    {
        return $query->where('devise', $devise);
    }

    /**
     * Scope pour rechercher par titulaire ou numéro de compte.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('numeroCompte', 'like', "%{$search}%")
              ->orWhereHas('client.user', function (Builder $subQuery) use ($search) {
                  $subQuery->where('nomComplet', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Scope pour appliquer le tri.
     *
     * @param Builder $query
     * @param string $sort
     * @param string $order
     * @return Builder
     */
    public function scopeSortBy(Builder $query, string $sort = 'dateCreation', string $order = 'desc'): Builder
    {
        $allowedSorts = ['dateCreation', 'derniereModification', 'numeroCompte'];
        $allowedOrders = ['asc', 'desc'];

        $sort = in_array($sort, $allowedSorts) ? $sort : 'dateCreation';
        $order = in_array($order, $allowedOrders) ? $order : 'desc';

        return $query->orderBy($sort, $order);
    }

    /**
     * Scope pour récupérer uniquement les comptes actifs (non archivés).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope pour récupérer uniquement les comptes archivés.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Archiver le compte (prépare pour stockage cloud).
     *
     * @param string|null $cloudPath
     * @return void
     */
    public function archive(?string $cloudPath = null): void
    {
        $this->update([
            'archived_at' => now(),
            'cloud_storage_path' => $cloudPath,
            'statut' => 'ferme', // Un compte archivé est fermé
        ]);
    }

    /**
     * Vérifier si le compte est archivé.
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Vérifier si le compte est actif (non archivé, non supprimé).
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return is_null($this->archived_at) 
            && is_null($this->deleted_at) 
            && $this->statut === 'actif';
    }
}
