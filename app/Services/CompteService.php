    /**
     * Récupère un compte par son ID
     * Stratégie : Recherche d'abord en local (Render), puis dans les archives (Neon)
     *
     * @param string $id
     * @param User|null $user
     * @return array
     */
    public function getCompteById(string $id, ?\App\Models\User $user = null): array
    {
        // 1. Recherche dans la base locale (Render)
        $compte = Compte::with(['client.user'])->find($id);

        if ($compte) {
            // Vérification des autorisations
            if ($user && $user->role === 'client') {
                $client = $user->client;
                if (!$client || $compte->client_id !== $client->id) {
                    return [
                        'error' => [
                            'code' => 'ACCESS_DENIED',
                            'message' => "Vous n'avez pas accès à ce compte",
                            'details' => ['compteId' => $id]
                        ],
                        'status' => 403
                    ];
                }
            }
            // Compte trouvé en local
            return [
                'data' => new CompteResource($compte),
                'archived' => false,
                'source' => 'render'
            ];
        }

        // 2. Recherche dans les archives (Neon)
        $archivedCompte = $this->archiveService->getArchivedCompteById($id);

        if ($archivedCompte) {
            // Vérification des autorisations pour compte archivé
            if ($user && $user->role === 'client') {
                $client = $user->client;
                if (!$client || $archivedCompte->client_id !== $client->id) {
                    return [
                        'error' => [
                            'code' => 'ACCESS_DENIED',
                            'message' => "Vous n'avez pas accès à ce compte archivé",
                            'details' => ['compteId' => $id]
                        ],
                        'status' => 403
                    ];
                }
            }
            // Formater le compte archivé
            return [
                'data' => $this->formatArchivedCompte($archivedCompte),
                'archived' => true,
                'source' => 'neon'
            ];
        }

        // 3. Compte non trouvé nulle part
        return [
            'error' => [
                'code' => 'COMPTE_NOT_FOUND',
                'message' => "Le compte avec l'ID spécifié n'existe pas",
                'details' => ['compteId' => $id]
            ],
            'status' => 404
        ];
    }

    /**
     * Formate un compte archivé pour la réponse
     */
    private function formatArchivedCompte($archivedCompte): array
    {
        return [
            'id' => $archivedCompte->id,
            'numeroCompte' => $archivedCompte->numerocompte,
            'titulaire' => $archivedCompte->client_nom ?? 'N/A',
            'type' => $archivedCompte->type,
            'solde' => (float) $archivedCompte->solde,
            'devise' => $archivedCompte->devise,
            'dateCreation' => $archivedCompte->created_at,
            'statut' => $archivedCompte->statut,
            'motifBlocage' => $archivedCompte->motifblocage,
            'archived' => true,
            'archived_at' => $archivedCompte->archived_at,
            'archive_reason' => $archivedCompte->archive_reason,
            'metadata' => [
                'source' => 'neon',
                'derniereModification' => $archivedCompte->updated_at,
                'client_email' => $archivedCompte->client_email,
                'client_telephone' => $archivedCompte->client_telephone,
            ]
        ];
    }
<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\User;
use App\Models\Client;
use App\Http\Requests\ListCompteRequest;
use App\Http\Resources\CompteResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Events\CompteCreated;

class CompteService
{
    protected $archiveService;

    public function __construct(CompteArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
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

        // 2. Si non trouvé ou archivé, chercher dans Neon (comptes fermés/bloqués/archivés)
        $archived = $this->archiveService->getArchivedCompte($numero);

        if ($archived) {
            // Vérifier si le client a le droit d'accéder à ce compte archivé
            if ($user->role === 'client' && $archived->client_email !== $user->email) {
                return [
                    'error' => true,
                    'code' => 403,
                    'message' => 'Accès non autorisé à ce compte'
                ];
            }
            
            // Compte trouvé dans les archives Neon
            return [
                'success' => true,
                'data' => [
                    'id' => $archived->id,
                    'numeroCompte' => $archived->numerocompte,
                    'titulaire' => $archived->client_nom,
                    'type' => $archived->type,
                    'solde' => $archived->solde,
                    'devise' => $archived->devise,
                    'dateCreation' => $archived->created_at,
                    'statut' => $archived->statut,
                    'motifBlocage' => $archived->motifblocage,
                    'archived' => true,
                    'archived_at' => $archived->archived_at,
                    'archive_reason' => $archived->archive_reason,
                    'metadata' => [
                        'source' => 'neon',
                        'client_email' => $archived->client_email,
                        'client_telephone' => $archived->client_telephone,
                    ]
                ],
                'message' => 'Compte archivé récupéré depuis Neon'
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
     * Archiver un compte par son numéro
     */
    public function archiveCompte(string $numeroCompte, ?string $reason = null): array
    {
        $compte = Compte::where('numeroCompte', $numeroCompte)->first();

        if (!$compte) {
            return [
                'error' => true,
                'code' => 404,
                'message' => "Le compte {$numeroCompte} n'existe pas"
            ];
        }

        // Archiver vers Neon
        $archive = $this->archiveService->archiveCompte($compte, null, $reason);

        return [
            'success' => true,
            'data' => [
                'numeroCompte' => $compte->numeroCompte,
                'archived_at' => $archive->archived_at,
                'archive_reason' => $archive->archive_reason,
            ],
            'message' => 'Compte archivé avec succès dans le cloud'
        ];
    }
}
