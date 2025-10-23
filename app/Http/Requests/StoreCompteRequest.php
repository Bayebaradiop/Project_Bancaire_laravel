<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'type' => ['required', Rule::in(['cheque', 'epargne'])],
            'devise' => ['sometimes', 'string', 'max:10'],
            'statut' => ['sometimes', Rule::in(['actif', 'bloque', 'ferme'])],
            'motifBlocage' => ['nullable', 'string'],
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
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.uuid' => 'L\'identifiant du client doit être un UUID valide.',
            'client_id.exists' => 'Le client spécifié n\'existe pas.',
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être soit "cheque" soit "epargne".',
            'devise.max' => 'La devise ne peut pas dépasser 10 caractères.',
            'statut.in' => 'Le statut doit être "actif", "bloque" ou "ferme".',
        ];
    }
}
