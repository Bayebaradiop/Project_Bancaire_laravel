<?php

namespace App\Services;

use App\Models\Compte;
use App\Http\Requests\ListCompteRequest;
use App\Http\Resources\CompteResource;

class CompteService
{
    /**
     * Récupérer la liste des comptes
     */
    public function getComptesList(ListCompteRequest $request): array
    {
        // 1. Extraire les filtres
        $filters = $this->extractFilters($request);
        
        // 2. Récupérer les comptes
        $paginator = $this->fetchComptes($filters);
        
        // 3. Transformer avec Resource
        $data = CompteResource::collection($paginator->items())->resolve();
        
        // 4. Formater la réponse
        return $this->formatResponse($paginator, $filters, $data);
    }

    private function extractFilters(ListCompteRequest $request): array
    {
        return [
            'type' => $request->getType(),
            'devise' => $request->getDevise(),
            'search' => $request->getSearch(),
            'sort' => $request->getSort(),
            'order' => $request->getOrder(),
            'limit' => $request->getLimit(),
        ];
    }

    private function fetchComptes(array $filters)
    {
        // Le Global Scope ActiveCompteScope filtre automatiquement :
        // - whereNull('archived_at')
        // - where('statut', 'actif')
        $query = Compte::with(['client.user']);

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($filters['limit'] ?? 10);
    }

    private function applyFilters($query, array $filters)
    {
        // Utiliser les scopes du Model au lieu de where() manuel
        if (!empty($filters['type'])) {
            $query->type($filters['type']);
        }

        if (!empty($filters['devise'])) {
            $query->devise($filters['devise']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $query->sortBy(
            $filters['sort'] ?? 'dateCreation',
            $filters['order'] ?? 'desc'
        );

        return $query;
    }
    
    private function formatResponse($paginator, array $filters, array $data): array
    {
        $queryParams = array_filter([
            'page' => null,
            'limit' => $filters['limit'] ?? 10,
            'type' => $filters['type'] ?? null,
            'devise' => $filters['devise'] ?? null,
            'search' => $filters['search'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'order' => $filters['order'] ?? null,
        ], fn($value) => $value !== null);
        
        $buildUrl = function($page) use ($queryParams) {
            $params = array_merge($queryParams, ['page' => $page]);
            return '/api/v1/comptes?' . http_build_query($params);
        };
        
        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'totalItems' => $paginator->total(),
                'itemsPerPage' => $paginator->perPage(),
                'hasNext' => $paginator->hasMorePages(),
                'hasPrevious' => $paginator->currentPage() > 1,
            ],
            'links' => [
                'self' => $buildUrl($paginator->currentPage()),
                'first' => $buildUrl(1),
                'last' => $buildUrl($paginator->lastPage()),
                'next' => $paginator->hasMorePages() ? $buildUrl($paginator->currentPage() + 1) : null,
                'previous' => $paginator->currentPage() > 1 ? $buildUrl($paginator->currentPage() - 1) : null,
            ],
        ];
    }
}
