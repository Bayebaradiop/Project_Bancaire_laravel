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
            'statut' => ['sometimes', 'string', Rule::in(['actif', 'bloque', 'ferme'])],
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
            'statut.in' => 'Le statut doit être "actif", "bloque" ou "ferme".',
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
     * Get the statut filter.
     *
     * @return string|null
     */
    public function getStatut(): ?string
    {
        return $this->input('statut');
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
