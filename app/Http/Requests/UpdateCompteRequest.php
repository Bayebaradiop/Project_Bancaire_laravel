<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompteRequest extends FormRequest
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
            'type' => ['sometimes', Rule::in(['cheque', 'epargne'])],
            'devise' => ['sometimes', 'string', 'max:10'],
            'statut' => ['sometimes', Rule::in(['actif', 'bloque', 'ferme'])],
            'motifBlocage' => ['nullable', 'string'],
            'version' => ['sometimes', 'integer'],
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
            'type.in' => 'Le type de compte doit être soit "chèque" soit "épargne".',
            'devise.max' => 'La devise ne peut pas dépasser 10 caractères.',
            'statut.in' => 'Le statut doit être "actif", "bloqué" ou "fermé".',
            'version.integer' => 'La version doit être un nombre entier.',
        ];
    }
}
