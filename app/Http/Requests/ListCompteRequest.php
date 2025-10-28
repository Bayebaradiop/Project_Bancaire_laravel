<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ListCompteRequest extends PaginationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'type' => ['sometimes', 'string', Rule::in(['epargne', 'cheque'])],
            'devise' => ['sometimes', 'string', 'max:10'],
            'numeroCompte' => ['sometimes', 'string', 'regex:/^CP\d{10}$/'],
            'search' => ['sometimes', 'string', 'max:255'],
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'type.in' => 'Le type doit être "epargne" ou "cheque".',
            'devise.max' => 'La devise ne peut pas dépasser 10 caractères.',
            'numeroCompte.regex' => 'Le numéro de compte doit être au format CPxxxxxxxxxx (CP suivi de 10 chiffres).',
            'search.max' => 'La recherche ne peut pas dépasser 255 caractères.',
        ]);
    }

    /**
     * Get the type filter.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->input('type');
    }

    /**
     * Get the devise filter.
     *
     * @return string|null
     */
    public function getDevise(): ?string
    {
        return $this->input('devise');
    }

    /**
     * Get the numeroCompte filter.
     *
     * @return string|null
     */
    public function getNumeroCompte(): ?string
    {
        return $this->input('numeroCompte');
    }

    /**
     * Get the search term.
     *
     * @return string|null
     */
    public function getSearch(): ?string
    {
        return $this->input('search');
    }

    /**
     * Get allowed sort fields for comptes.
     *
     * @return array
     */
    protected function getAllowedSortFields(): array
    {
        return ['dateCreation', 'derniereModification', 'numeroCompte', 'type', 'statut'];
    }

    /**
     * Get default sort field for comptes.
     *
     * @return string
     */
    protected function getDefaultSort(): string
    {
        return 'dateCreation';
    }
}
