<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloquerCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Seuls les administrateurs peuvent bloquer un compte
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
            'motif' => 'required|string|min:10|max:500',
            'date_debut_blocage' => 'required|date|after_or_equal:today',
            'duree' => 'required|integer|min:1|max:365',
            'unite' => 'required|string|in:jours,mois',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de blocage est obligatoire',
            'motif.min' => 'Le motif doit contenir au moins 10 caractères',
            'motif.max' => 'Le motif ne peut pas dépasser 500 caractères',
            'date_debut_blocage.required' => 'La date de début de blocage est obligatoire',
            'date_debut_blocage.date' => 'La date de début de blocage doit être une date valide',
            'date_debut_blocage.after_or_equal' => 'La date de début de blocage ne peut pas être dans le passé',
            'duree.required' => 'La durée de blocage est obligatoire',
            'duree.integer' => 'La durée doit être un nombre entier',
            'duree.min' => 'La durée minimale est de 1',
            'duree.max' => 'La durée maximale est de 365',
            'unite.required' => 'L\'unité de temps est obligatoire',
            'unite.in' => 'L\'unité doit être "jours" ou "mois"',
        ];
    }
}
