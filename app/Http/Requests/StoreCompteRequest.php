<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ValidNciSenegalais;
use App\Rules\ValidTelephoneSenegalais;

class StoreCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:epargne,courant,cheque',
            'devise' => 'required|in:FCFA,USD,EUR',
            
            // Client
            'client' => 'required|array',
            'client.id' => 'nullable|exists:clients,id',
            'client.titulaire' => 'required_if:client.id,null|string|max:255',
            'client.nci' => [
                'required_if:client.id,null',
                'string',
                new ValidNciSenegalais(),
                'unique:users,nci'
            ],
            'client.email' => 'required_if:client.id,null|email|unique:users,email',
            'client.telephone' => [
                'required_if:client.id,null',
                'string',
                new ValidTelephoneSenegalais(),
                'unique:users,telephone'
            ],
            'client.adresse' => 'required_if:client.id,null|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est requis',
            'type.in' => 'Le type de compte doit être epargne, courant ou cheque',
            'devise.required' => 'La devise est requise',
            'devise.in' => 'La devise doit être FCFA, USD ou EUR',
            
            // Client
            'client.required' => 'Les informations du client sont requises',
            'client.id.exists' => 'Le client spécifié n\'existe pas',
            'client.titulaire.required_if' => 'Le nom du titulaire est requis',
            'client.nci.required_if' => 'Le NCI est requis',
            'client.nci.unique' => 'Ce NCI est déjà utilisé',
            'client.email.required_if' => 'L\'email est requis',
            'client.email.email' => 'L\'email doit être valide',
            'client.email.unique' => 'Cet email est déjà utilisé',
            'client.telephone.required_if' => 'Le téléphone est requis',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé',
            'client.adresse.required_if' => 'L\'adresse est requise',
        ];
    }
}
