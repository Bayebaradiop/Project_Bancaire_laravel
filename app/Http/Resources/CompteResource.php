<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
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
            'numeroCompte' => $this->numeroCompte,
            'titulaire' => $this->client->user->nomComplet ?? null,
            'type' => $this->type,
            'devise' => $this->devise,
            'dateCreation' => $this->dateCreation?->toIso8601String(),
            'statut' => $this->statut,
            'motifBlocage' => $this->motifBlocage,
            'metadata' => [
                'derniereModification' => $this->derniereModification?->toIso8601String(),
                'version' => $this->version,
            ],
        ];
    }
}
