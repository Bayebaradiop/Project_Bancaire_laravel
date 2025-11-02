<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Compte;

class TransfertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Le client ne peut transférer que depuis son propre compte, l'admin peut transférer depuis n'importe quel compte
        if (auth()->user()->role === 'admin') {
            return true;
        }

        $numeroCompteSource = $this->input('compte_source');
        $compteSource = Compte::where('numeroCompte', $numeroCompteSource)->first();
        
        return $compteSource && $compteSource->client_id === auth()->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'compte_source' => [
                'required',
                'string',
                'exists:comptes,numeroCompte',
                'different:compte_destinataire',
                function ($attribute, $value, $fail) {
                    $compte = Compte::where('numeroCompte', $value)->first();
                    if (!$compte) {
                        $fail('Le compte source n\'existe pas.');
                        return;
                    }
                    
                    if ($compte->statut !== 'actif') {
                        $fail('Le compte source doit être actif.');
                        return;
                    }
                    
                    // Calculer les frais de transfert (0.5%, min 100 FCFA, max 5000 FCFA)
                    $montant = $this->input('montant', 0);
                    $frais = max(100, min(5000, $montant * 0.005));
                    $montantTotal = $montant + $frais;
                    
                    // Vérifier le solde disponible (montant + frais)
                    if ($compte->getSolde() < $montantTotal) {
                        $fail('Solde insuffisant. Montant demandé: ' . $montant . ', Frais: ' . $frais . ', Total: ' . $montantTotal . '. Solde disponible: ' . $compte->getSolde() . ' ' . $compte->devise);
                    }
                },
            ],
            'compte_destinataire' => [
                'required',
                'string',
                'exists:comptes,numeroCompte',
                'different:compte_source',
                function ($attribute, $value, $fail) {
                    $compte = Compte::where('numeroCompte', $value)->first();
                    if ($compte && $compte->statut !== 'actif') {
                        $fail('Le compte destinataire doit être actif.');
                    }
                },
            ],
            'montant' => 'required|numeric|min:1000|max:10000000',
            'devise' => 'required|string|in:FCFA,EUR,USD',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'compte_source.required' => 'Le numéro de compte source est requis',
            'compte_source.exists' => 'Le compte source n\'existe pas',
            'compte_source.different' => 'Le compte source et destinataire doivent être différents',
            'compte_destinataire.required' => 'Le numéro de compte destinataire est requis',
            'compte_destinataire.exists' => 'Le compte destinataire n\'existe pas',
            'compte_destinataire.different' => 'Le compte destinataire et source doivent être différents',
            'montant.required' => 'Le montant est requis',
            'montant.min' => 'Le montant minimum pour un transfert est de 1000 :devise',
            'montant.max' => 'Le montant maximum est de 10,000,000 :devise',
            'devise.required' => 'La devise est requise',
            'devise.in' => 'La devise doit être FCFA, EUR ou USD',
        ];
    }
}
