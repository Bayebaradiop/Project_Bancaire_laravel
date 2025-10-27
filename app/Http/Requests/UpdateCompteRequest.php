<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * US 2.3 - Seuls les administrateurs peuvent modifier les comptes
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     * US 2.3 - Tous les champs sont optionnels mais au moins un doit être fourni
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $compteId = $this->route('compteId');
        
        return [
            // Informations du titulaire (User)
            'titulaire' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->getUserIdFromCompte($compteId))],
            'telephone' => [
                'sometimes',
                'regex:/^(77|78|76|75|70)[0-9]{7}$/',
                Rule::unique('users', 'telephone')->ignore($this->getUserIdFromCompte($compteId))
            ],
            'adresse' => ['sometimes', 'string', 'max:255'],
            
            // Informations du compte
            'type' => ['sometimes', Rule::in(['cheque', 'epargne'])],
            'devise' => ['sometimes', 'string', 'max:10'],
        ];
    }

    /**
     * Configure the validator instance.
     * US 2.3 - Au moins un champ doit être fourni
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty(array_filter($this->all()))) {
                $validator->errors()->add('fields', 'Au moins un champ doit être fourni pour la modification.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
            'telephone.regex' => 'Le numéro de téléphone doit être un numéro sénégalais valide (77, 78, 76, 75 ou 70 suivi de 7 chiffres).',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé par un autre compte.',
            'adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'adresse.max' => 'L\'adresse ne peut pas dépasser 255 caractères.',
            'type.in' => 'Le type de compte doit être soit "cheque" soit "epargne".',
            'devise.max' => 'La devise ne peut pas dépasser 10 caractères.',
        ];
    }

    /**
     * Helper pour récupérer l'ID du user associé au compte
     * Utilisé pour ignorer l'utilisateur actuel lors de la validation d'unicité
     */
    protected function getUserIdFromCompte($compteId)
    {
        if (!$compteId) {
            return null;
        }

        $compte = \App\Models\Compte::with('client.user')->find($compteId);
        return $compte?->client?->user?->id;
    }
}
