<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Compte;

class DepotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Seul l'admin peut effectuer un dépôt
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'compte_destinataire' => [
                'required',
                'string',
                'exists:comptes,numeroCompte',
                function ($attribute, $value, $fail) {
                    $compte = Compte::where('numeroCompte', $value)->first();
                    if (!$compte) {
                        return;
                    }
                    
                    if ($compte->statut === 'bloqué') {
                        $fail('Impossible d\'effectuer un dépôt sur un compte bloqué.');
                        return;
                    }
                    
                    if ($compte->statut === 'fermé') {
                        $fail('Impossible d\'effectuer un dépôt sur un compte fermé.');
                        return;
                    }
                    
                    if ($compte->statut !== 'actif') {
                        $fail('Le compte destinataire doit être actif pour recevoir un dépôt.');
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
            'compte_destinataire.required' => 'Le numéro de compte destinataire est requis',
            'compte_destinataire.exists' => 'Le compte destinataire n\'existe pas',
            'montant.required' => 'Le montant est requis',
            'montant.min' => 'Le montant minimum est de 500 :devise',
            'montant.max' => 'Le montant maximum est de 10,000,000 :devise',
            'devise.required' => 'La devise est requise',
            'devise.in' => 'La devise doit être FCFA, EUR ou USD',
        ];
    }
}
