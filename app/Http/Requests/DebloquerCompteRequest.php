<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DebloquerCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Seuls les administrateurs peuvent débloquer un compte
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
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de déblocage est obligatoire',
            'motif.min' => 'Le motif doit contenir au moins 10 caractères',
            'motif.max' => 'Le motif ne peut pas dépasser 500 caractères',
        ];
    }
}
