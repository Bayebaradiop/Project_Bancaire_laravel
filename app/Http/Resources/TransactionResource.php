<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            '_id' => $this->_id ?? $this->id,
            'id' => $this->_id ?? $this->id,
            'numeroTransaction' => $this->numeroTransaction,
            'type' => $this->type,
            'montant' => (float) $this->montant,
            'devise' => $this->devise,
            'frais' => (float) $this->frais,
            'statut' => $this->statut,
            'description' => $this->description,
            'date' => $this->created_at?->format('Y-m-d H:i:s'),
            'peut_etre_annulee' => $this->peutEtreAnnulee(),
            
            // IDs des comptes (cross-database relations not supported yet)
            'compte_source_id' => $this->compte_source_id,
            'compte_destinataire_id' => $this->compte_destinataire_id,
            
            // Note: Relations with PostgreSQL Compte model disabled for MongoDB
            // To enable, implement manual loading via Compte::find($this->compte_source_id)
            
            // Transaction parent (pour annulations)
            'transaction_parent' => $this->when($this->transactionParent, function() {
                return [
                    'id' => $this->transactionParent->id,
                    'numeroTransaction' => $this->transactionParent->numeroTransaction,
                    'type' => $this->transactionParent->type,
                ];
            }),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
