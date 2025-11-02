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
            'id' => $this->id,
            'numeroTransaction' => $this->numeroTransaction,
            'type' => $this->type,
            'montant' => (float) $this->montant,
            'devise' => $this->devise,
            'frais' => (float) $this->frais,
            'statut' => $this->statut,
            'description' => $this->description,
            'date' => $this->created_at?->format('Y-m-d H:i:s'),
            'peut_etre_annulee' => $this->peutEtreAnnulee(),
            
            // Compte principal
            'compte' => $this->when($this->compte, function() {
                return [
                    'id' => $this->compte->id,
                    'numeroCompte' => $this->compte->numeroCompte,
                    'typeCompte' => $this->compte->typeCompte,
                ];
            }),
            
            // Compte source (pour transferts et retraits)
            'compte_source' => $this->when($this->compteSource, function() {
                return [
                    'id' => $this->compteSource->id,
                    'numeroCompte' => $this->compteSource->numeroCompte,
                    'typeCompte' => $this->compteSource->typeCompte,
                ];
            }),
            
            // Compte destinataire (pour dépôts et transferts)
            'compte_destinataire' => $this->when($this->compteDestinataire, function() {
                return [
                    'id' => $this->compteDestinataire->id,
                    'numeroCompte' => $this->compteDestinataire->numeroCompte,
                    'typeCompte' => $this->compteDestinataire->typeCompte,
                ];
            }),
            
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
