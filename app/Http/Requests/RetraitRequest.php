<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Compte;

class RetraitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Le client peut retirer de son propre compte, l'admin peut retirer de n'importe quel compte
        if (auth()->user()->role === 'admin') {
            return true;
        }

        $numeroCompte = $this->input('compte_source');
        $compte = Compte::where('numeroCompte', $numeroCompte)->first();
        
        return $compte && $compte->client_id === auth()->user()->id;
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
                    
                    // Vérifier le solde disponible
                    $montant = $this->input('montant', 0);
                    if ($compte->getSolde() < $montant) {
                        $fail('Solde insuffisant. Solde disponible: ' . $compte->getSolde() . ' ' . $compte->devise);
                    }
                },
            ],
            'montant' => 'required|numeric|min:500|max:10000000',
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
            'montant.required' => 'Le montant est requis',
            'montant.min' => 'Le montant minimum est de 500 :devise',
            'montant.max' => 'Le montant maximum est de 10,000,000 :devise',
            'devise.required' => 'La devise est requise',
            'devise.in' => 'La devise doit être FCFA, EUR ou USD',
        ];
    }
}
