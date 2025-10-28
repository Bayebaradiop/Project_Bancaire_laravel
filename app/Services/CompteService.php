<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\User;
use App\Models\Client;
use App\Http\Requests\ListCompteRequest;
use App\Http\Resources\CompteResource;
use App\Repositories\CompteRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Events\CompteCreated;

class CompteService
{
    protected CompteRepository $compteRepository;

    public function __construct(CompteRepository $compteRepository)
    {
        $this->compteRepository = $compteRepository;
    }
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

    /**
     * Supprimer un compte (soft delete) et l'archiver dans Neon
     */
    public function deleteAndArchive(string $numeroCompte): array
    {
        DB::beginTransaction();

        try {
            // Trouver le compte
            $compte = $this->compteRepository->findByNumero($numeroCompte);

            if (!$compte) {
                return [
                    'success' => false,
                    'message' => "Le compte {$numeroCompte} n'existe pas",
                    'code' => 404
                ];
            }

            // Vérifier si le compte est déjà supprimé
            if ($compte->trashed()) {
                return [
                    'success' => false,
                    'message' => "Le compte {$numeroCompte} est déjà supprimé",
                    'code' => 400
                ];
            }

            // Supprimer et archiver
            $this->compteRepository->deleteAndArchive($compte);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Compte supprimé avec succès et archivé dans Neon',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'statut' => 'ferme',
                    'dateFermeture' => now()->toIso8601String(),
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restaurer un compte supprimé
     */
    public function restore(string $id): array
    {
        DB::beginTransaction();

        try {
            $compte = $this->compteRepository->restore($id);

            if (!$compte) {
                return [
                    'success' => false,
                    'message' => "Le compte avec l'ID {$id} n'existe pas ou n'est pas supprimé",
                    'code' => 404
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Compte restauré avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'statut' => 'actif',
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Récupérer les comptes archivés depuis Neon
     */
    public function getArchived(int $perPage = 10): array
    {
        $paginator = $this->compteRepository->getArchived($perPage);

        return [
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'totalItems' => $paginator->total(),
                'itemsPerPage' => $paginator->perPage(),
            ]
        ];
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

    /**
     * Récupérer un compte par son numéro (base active ou archive)
     * Vérifie les autorisations en fonction du rôle de l'utilisateur
     */
    /**
     * Récupérer un compte par son ID (US 2.1)
     * Stratégie de recherche dual-database:
     * 1. Par défaut : recherche dans PostgreSQL (comptes actifs)
     * 2. Si non trouvé : recherche dans Neon (comptes archivés)
     * 
     * Autorisation:
     * - Admin : peut récupérer n'importe quel compte par ID
     * - Client : peut récupérer uniquement ses propres comptes par ID
     */
    public function getCompteById(string $id, User $user): array
    {
        // 1. Chercher d'abord dans la base principale (PostgreSQL) - tous les comptes (actifs, bloqués, fermés)
        $compte = Compte::where('id', $id)
            ->with(['client.user'])
            ->first();

        if ($compte) {
            // Vérifier l'autorisation
            if ($user->role === 'client') {
                // Les clients ne peuvent voir que leurs propres comptes
                if (!$compte->client || $compte->client->user_id !== $user->id) {
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'ACCESS_DENIED',
                            'message' => 'Accès non autorisé à ce compte',
                            'details' => [
                                'compteId' => $id
                            ]
                        ],
                        'http_code' => 403
                    ];
                }
            }
            
            // Compte trouvé dans PostgreSQL (actif, bloqué ou fermé)
            return [
                'success' => true,
                'data' => new CompteResource($compte),
                'message' => 'Compte récupéré avec succès'
            ];
        }

        // 2. Si non trouvé dans PostgreSQL, chercher dans les archives Neon
        $archivedCompte = DB::connection('neon')
            ->table('archives_comptes')
            ->where('id', $id)
            ->first();

        if ($archivedCompte) {
            // Récupérer les infos du client depuis PostgreSQL si disponible
            $clientInfo = null;
            if ($archivedCompte->client_id) {
                $client = Client::with('user')->find($archivedCompte->client_id);
                if ($client) {
                    // Vérifier l'autorisation pour les archives
                    if ($user->role === 'client' && $client->user_id !== $user->id) {
                        return [
                            'success' => false,
                            'error' => [
                                'code' => 'ACCESS_DENIED',
                                'message' => 'Accès non autorisé à ce compte archivé',
                                'details' => [
                                    'compteId' => $id
                                ]
                            ],
                            'http_code' => 403
                        ];
                    }
                    $clientInfo = $client->user->nomComplet ?? 'N/A';
                }
            }

            // Compte archivé trouvé dans Neon
            return [
                'success' => true,
                'data' => [
                    'id' => $archivedCompte->id,
                    'numeroCompte' => $archivedCompte->numeroCompte,
                    'titulaire' => $clientInfo ?? 'N/A',
                    'type' => $archivedCompte->type,
                    'solde' => (float) $archivedCompte->solde,
                    'devise' => $archivedCompte->devise ?? 'FCFA',
                    'dateCreation' => $archivedCompte->created_at,
                    'statut' => $archivedCompte->statut,
                    'motifBlocage' => null,
                    'metadata' => [
                        'derniereModification' => $archivedCompte->updated_at,
                        'version' => 1,
                        'archived' => true,
                        'dateFermeture' => $archivedCompte->dateFermeture
                    ]
                ],
                'message' => 'Compte récupéré avec succès depuis les archives Neon'
            ];
        }

        // 3. Compte introuvable dans les deux bases
        return [
            'success' => false,
            'error' => [
                'code' => 'COMPTE_NOT_FOUND',
                'message' => "Le compte avec l'ID spécifié n'existe pas",
                'details' => [
                    'compteId' => $id
                ]
            ],
            'http_code' => 404
        ];
    }

    public function getCompteByNumero(string $numero, User $user): ?array
    {
        // 1. Chercher d'abord dans la base principale (Render) - comptes actifs uniquement
        $compte = Compte::where('numeroCompte', $numero)
            ->whereNull('archived_at')
            ->where('statut', 'actif')
            ->with(['client.user'])
            ->first();

        if ($compte) {
            // Vérifier si le client a le droit d'accéder à ce compte
            if ($user->role === 'client') {
                // Les clients ne peuvent voir que leurs propres comptes
                if (!$compte->client || $compte->client->user_id !== $user->id) {
                    return [
                        'error' => true,
                        'code' => 403,
                        'message' => 'Accès non autorisé à ce compte'
                    ];
                }
            }
            
            // Compte actif trouvé dans la base principale
            return [
                'success' => true,
                'data' => new CompteResource($compte),
                'message' => 'Compte actif récupéré avec succès'
            ];
        }

        // 2. Si non trouvé, chercher dans les archives Neon
        $existsInArchive = $this->compteRepository->existsInArchive($compte->id ?? '');

        if ($existsInArchive) {
            // Pour l'instant, on ne retourne pas les détails des comptes archivés via cette méthode
            // Ils sont accessibles via l'endpoint dédié /archives
            return [
                'error' => true,
                'code' => 404,
                'message' => "Le compte avec le numéro {$numero} n'existe pas"
            ];
        }

        // 3. Compte introuvable dans les deux bases
        return [
            'error' => true,
            'code' => 404,
            'message' => "Le compte avec le numéro {$numero} n'existe pas"
        ];
    }

    /**
     * Créer un nouveau compte bancaire
     * Gère aussi la création du client si nécessaire
     */
    public function createCompte(array $data): array
    {
        DB::beginTransaction();
        
        try {
            $password = null;
            $code = null;

            // 1. Vérifier l'existence du client
            if (!empty($data['client']['id'])) {
                $client = Client::findOrFail($data['client']['id']);
            } else {
                // 2. Créer l'utilisateur et le client s'il n'existe pas
                $password = Client::generatePassword();
                $code = Client::generateCode();

                // Créer l'utilisateur
                $user = User::create([
                    'nomComplet' => $data['client']['titulaire'],
                    'nci' => $data['client']['nci'],
                    'email' => $data['client']['email'],
                    'telephone' => $data['client']['telephone'],
                    'adresse' => $data['client']['adresse'],
                    'password' => Hash::make($password),
                    'code' => $code,
                ]);

                // Créer le client
                $client = Client::create([
                    'user_id' => $user->id,
                ]);

                // Note: Plus besoin de session, on dispatch l'event directement après création du compte
            }

            // 3. Créer le compte
            $compte = Compte::create([
                'numeroCompte' => Compte::generateNumeroCompte(),
                'type' => $data['type'],
                'devise' => $data['devise'],
                'statut' => 'actif',
                'client_id' => $client->id,
            ]);

            // 4. Dispatcher l'event si nouveau client créé
            if ($password && $code) {
                event(new CompteCreated($compte, $password, $code));
            }

            // Charger les relations
            $compte->load(['client.user', 'transactions']);

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'titulaire' => $compte->client->user->nomComplet ?? 'N/A',
                    'type' => $compte->type,
                    'solde' => $compte->solde,
                    'devise' => $compte->devise,
                    'dateCreation' => $compte->dateCreation->toIso8601String(),
                    'statut' => $compte->statut,
                    'metadata' => [
                        'derniereModification' => $compte->derniereModification->toIso8601String(),
                        'version' => 1,
                    ],
                ],
                'message' => 'Compte créé avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Archiver un compte par son numéro (legacy method - now uses deleteAndArchive)
     */
    public function archiveCompte(string $numeroCompte, ?string $reason = null): array
    {
        return $this->deleteAndArchive($numeroCompte);
    }
}
