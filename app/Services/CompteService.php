<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\User;
use App\Http\Requests\ListCompteRequest;
use App\Http\Resources\CompteResource;

class CompteService
{
    /**
     * Récupérer la liste des comptes avec autorisation
     * - Admin : voit tous les comptes
     * - Client : voit uniquement ses propres comptes
     */
    public function getComptesList(ListCompteRequest $request, ?User $user = null): array
    {
        // 1. Extraire les filtres
        $filters = $this->extractFilters($request);
        
        // 2. Récupérer les comptes avec autorisation
        $paginator = $this->fetchComptes($filters, $user);
        
        // 3. Transformer avec Resource
        $data = CompteResource::collection($paginator->items())->resolve();
        
        // 4. Formater la réponse
        return $this->formatResponse($paginator, $filters, $data);
    }

    private function extractFilters(ListCompteRequest $request): array
    {
        return [
            'type' => $request->getType(),
            'statut' => $request->getStatut(),
            'devise' => $request->getDevise(),
            'search' => $request->getSearch(),
            'sort' => $request->getSort(),
            'order' => $request->getOrder(),
            'limit' => $request->getLimit(),
        ];
    }

    private function fetchComptes(array $filters, ?User $user = null)
    {
        $query = Compte::with(['client.user'])
            ->whereNull('archived_at')
            ->where('statut', 'actif');

        // Autorisation : Client voit uniquement ses comptes
        if ($user && $user->role === 'client') {
            // Récupérer le client_id de l'utilisateur
            $client = $user->client;
            if ($client) {
                $query->where('client_id', $client->id);
            } else {
                // Si l'utilisateur n'a pas de client associé, retourner vide
                $query->whereRaw('1 = 0'); // Aucun résultat
            }
        }
        // Admin voit tous les comptes (pas de filtre supplémentaire)

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($filters['limit'] ?? 10);
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['type'])) {
            $query->type($filters['type']);
        }

        if (!empty($filters['statut'])) {
            $query->statut($filters['statut']);
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
            'statut' => $filters['statut'] ?? null,
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
