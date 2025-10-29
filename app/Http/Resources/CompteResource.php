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
        $data = [
            'id' => $this->id,
            'numeroCompte' => $this->numeroCompte,
            'titulaire' => $this->client->user->nomComplet ?? null,
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->dateCreation?->toIso8601String(),
            'statut' => $this->statut,
            'motifBlocage' => $this->motifBlocage,
        ];

        // Ajouter les informations de blocage programmé
        if ($this->blocage_programme) {
            $dateDebut = $this->dateDebutBlocage ? \Carbon\Carbon::parse($this->dateDebutBlocage)->format('d/m/Y') : 'date non définie';
            $dateFin = $this->dateFinBlocage ? \Carbon\Carbon::parse($this->dateFinBlocage)->format('d/m/Y') : 'durée indéterminée';
            
            $message = $this->dateFinBlocage 
                ? "Ce compte sera bloqué le {$dateDebut} jusqu'au {$dateFin}"
                : "Ce compte sera bloqué le {$dateDebut}";
            
            $data['blocage_info'] = [
                'en_cours' => true,
                'message' => $message,
                'dateDebutBlocage' => $dateDebut,
                'dateFinBlocage' => $this->dateFinBlocage ? $dateFin : null,
                'motif' => $this->motifBlocage ?? 'Blocage administratif',
            ];
        } else {
            $data['blocage_info'] = null;
        }

        $data['metadata'] = [
            'derniereModification' => $this->derniereModification?->toIso8601String(),
            'version' => $this->version,
            'location' => 'PostgreSQL', // Le compte est dans PostgreSQL tant qu'il n'est pas bloqué définitivement
        ];

        return $data;
    }
}
