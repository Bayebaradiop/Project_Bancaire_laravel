<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaginationRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in($this->getAllowedSortFields())],
            'order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
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
            'page.integer' => 'Le numéro de page doit être un nombre entier.',
            'page.min' => 'Le numéro de page doit être supérieur ou égal à 1.',
            'limit.integer' => 'La limite doit être un nombre entier.',
            'limit.min' => 'La limite doit être supérieure ou égale à 1.',
            'limit.max' => 'La limite ne peut pas dépasser 100 éléments.',
            'sort.in' => 'Le champ de tri n\'est pas valide.',
            'order.in' => 'L\'ordre doit être "asc" ou "desc".',
        ];
    }

    /**
     * Get the page number.
     *
     * @return int
     */
    public function getPage(): int
    {
        return $this->input('page', 1);
    }

    /**
     * Get the limit.
     *
     * @return int
     */
    public function getLimit(): int
    {
        return min($this->input('limit', 10), 100);
    }

    /**
     * Get the sort field.
     *
     * @return string
     */
    public function getSort(): string
    {
        return $this->input('sort', $this->getDefaultSort());
    }

    /**
     * Get the order.
     *
     * @return string
     */
    public function getOrder(): string
    {
        return $this->input('order', 'desc');
    }

    /**
     * Get allowed sort fields.
     * Override this method in child classes.
     *
     * @return array
     */
    protected function getAllowedSortFields(): array
    {
        return ['dateCreation', 'derniereModification', 'numeroCompte'];
    }

    /**
     * Get default sort field.
     * Override this method in child classes.
     *
     * @return string
     */
    protected function getDefaultSort(): string
    {
        return 'dateCreation';
    }
}
