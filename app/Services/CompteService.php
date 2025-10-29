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
use Illuminate\Support\Facades\Log;
use App\Events\CompteCreated;

class CompteService
{
    protected CompteRepository $compteRepository;
    protected CompteArchiveService $compteArchiveService;

    public function __construct(
        CompteRepository $compteRepository,
        CompteArchiveService $compteArchiveService
    ) {
        $this->compteRepository = $compteRepository;
        $this->compteArchiveService = $compteArchiveService;
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
            // Vérifier si le compte est déjà archivé dans Neon
            $archivedCompte = DB::connection('neon')
                ->table('comptes_archives')
                ->where('numerocompte', $numeroCompte)
                ->first();

            if ($archivedCompte) {
                return [
                    'success' => false,
                    'message' => "Le compte {$numeroCompte} est déjà archivé",
                    'code' => 400
                ];
            }

            // Trouver le compte actif
            $compte = $this->compteRepository->findByNumero($numeroCompte);

            if (!$compte) {
                return [
                    'success' => false,
                    'message' => "Le compte {$numeroCompte} n'existe pas",
                    'code' => 404
                ];
            }

            // Vérifier si le compte est déjà supprimé (soft delete dans PostgreSQL)
            if ($compte->trashed()) {
                return [
                    'success' => false,
                    'message' => "Le compte {$numeroCompte} est déjà supprimé",
                    'code' => 400
                ];
            }

            // Vérifier que le compte n'est pas de type chèque
            if ($compte->type === 'cheque') {
                return [
                    'success' => false,
                    'message' => 'Les comptes chèque ne peuvent pas être supprimés',
                    'code' => 400
                ];
            }

            // Vérifier si le compte a un blocage programmé en cours
            if ($compte->blocage_programme) {
                $dateBloqueProgramme = $compte->dateDebutBlocage 
                    ? \Carbon\Carbon::parse($compte->dateDebutBlocage)->format('d/m/Y')
                    : 'date non définie';
                    
                return [
                    'success' => false,
                    'message' => "Ce compte ne peut pas être supprimé car il a un blocage programmé prévu le {$dateBloqueProgramme}. Veuillez d'abord annuler le blocage ou attendre son exécution.",
                    'code' => 400
                ];
            }

            // Vérifier si le compte est bloqué (statut = 'bloque')
            if ($compte->statut === 'bloque') {
                return [
                    'success' => false,
                    'message' => "Ce compte est actuellement bloqué. Veuillez d'abord le débloquer avant de le supprimer.",
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
            'devise' => $request->getDevise(),
            'numeroCompte' => $request->getNumeroCompte(),
            'search' => $request->getSearch(),
            'sort' => $request->getSort(),
            'order' => $request->getOrder(),
            'limit' => $request->getLimit(),
        ];
    }

    private function fetchComptes(array $filters, ?User $user = null)
    {
        // Le Global Scope ActiveCompteScope filtre automatiquement selon US 2.0 :
        // - Comptes CHÈQUE : tous (actif, bloqué, fermé) NON archivés
        // - Comptes ÉPARGNE : ACTIFS uniquement NON archivés
        // - "Liste compte non supprimés type cheque ou compte Epargne Actif"
        $query = Compte::with(['client.user']);

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
        // Utiliser les scopes du Model
        // Option 1: Scopes avec préfixe "par" (nouveaux - plus explicites)
        if (!empty($filters['type'])) {
            $query->parType($filters['type']);
        }

        if (!empty($filters['devise'])) {
            $query->parDevise($filters['devise']);
        }

        // Option 2: Scopes sans préfixe (existants - pour compatibilité)
        if (!empty($filters['numeroCompte'])) {
            $query->numero($filters['numeroCompte']);
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
            'numeroCompte' => $filters['numeroCompte'] ?? null,
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
        $compte = Compte::actifs()
            ->where('numeroCompte', $numero)
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

        // 2. Si non trouvé dans la base principale, chercher dans les archives Neon par numéro
        try {
            $archivedCompte = $this->compteRepository->getArchivedByNumero($numero);
            
            if ($archivedCompte) {
                // Vérifier les droits d'accès pour les clients
                if ($user->role === 'client') {
                    // Les clients ne peuvent voir que leurs propres comptes
                    if ($archivedCompte->client_id !== $user->client->id ?? null) {
                        return [
                            'error' => true,
                            'code' => 403,
                            'message' => 'Accès non autorisé à ce compte'
                        ];
                    }
                }

                return [
                    'error' => true,
                    'code' => 410, // 410 Gone - Le compte existe mais est archivé
                    'message' => "Le compte {$numero} est archivé et n'est plus actif. Consultez /api/v1/comptes/archives pour plus de détails."
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche dans les archives', [
                'numero' => $numero,
                'error' => $e->getMessage()
            ]);
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
            Log::info('Début création compte', ['data' => $data]);
            
            $password = null;
            $code = null;

            // 1. Vérifier l'existence du client
            if (!empty($data['client']['id'])) {
                $client = Client::findOrFail($data['client']['id']);
                Log::info('Client existant trouvé', ['client_id' => $client->id]);
            } else {
                // 2. Créer l'utilisateur et le client s'il n'existe pas
                $password = Client::generatePassword();
                $code = Client::generateCode();

                Log::info('Création nouvel utilisateur', [
                    'nomComplet' => $data['client']['titulaire'],
                    'email' => $data['client']['email']
                ]);

                // Créer l'utilisateur
                $user = User::create([
                    'nomComplet' => $data['client']['titulaire'],
                    'nci' => $data['client']['nci'],
                    'email' => $data['client']['email'],
                    'telephone' => $data['client']['telephone'],
                    'adresse' => $data['client']['adresse'],
                    'password' => Hash::make($password),
                    'code' => $code,
                    'role' => 'client', // Ajout explicite du rôle
                ]);

                Log::info('Utilisateur créé', ['user_id' => $user->id]);

                // Créer le client
                $client = Client::create([
                    'user_id' => $user->id,
                ]);

                Log::info('Client créé', ['client_id' => $client->id]);

                // Note: Plus besoin de session, on dispatch l'event directement après création du compte
            }

            // 3. Créer le compte
            Log::info('Création du compte bancaire');
            
            $compte = Compte::create([
                'numeroCompte' => Compte::generateNumeroCompte(),
                'type' => $data['type'],
                'devise' => $data['devise'],
                'statut' => 'actif',
                'client_id' => $client->id,
            ]);

            Log::info('Compte créé', ['compte_id' => $compte->id, 'numeroCompte' => $compte->numeroCompte]);

            // 4. Dispatcher l'event si nouveau client créé
            if ($password && $code) {
                Log::info('Dispatch event CompteCreated');
                event(new CompteCreated($compte, $password, $code));
            }

            // Charger les relations
            $compte->load(['client.user', 'transactions']);

            DB::commit();

            Log::info('Création compte réussie', ['compte_id' => $compte->id]);

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
            Log::error('Erreur création compte', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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

    /**
     * Bloquer un compte épargne avec archivage dans Neon
     * 
     * Règles :
     * - Si date de blocage = aujourd'hui → Blocage immédiat + Archivage dans Neon
     * - Si date de blocage future → Blocage programmé (reste actif dans PostgreSQL)
     * - Seuls les comptes épargne peuvent être bloqués
     */
    public function bloquerCompte(string $compteId, array $data): array
    {
        DB::beginTransaction();

        try {
            // 1. Chercher d'abord dans PostgreSQL
            $compte = Compte::withoutGlobalScopes()->find($compteId);

            if (!$compte) {
                // Vérifier si le compte est déjà dans Neon (archivé/bloqué)
                try {
                    $compteArchive = DB::connection('neon')
                        ->table('comptes_archives')
                        ->where('id', $compteId)
                        ->first();
                    
                    if ($compteArchive) {
                        return [
                            'success' => false,
                            'message' => 'Ce compte est déjà bloqué et se trouve dans la base d\'archivage (Neon)',
                            'http_code' => 400
                        ];
                    }
                } catch (\Exception $e) {
                    // Neon non accessible, continuer
                }

                return [
                    'success' => false,
                    'message' => 'Ce compte n\'existe pas',
                    'http_code' => 404
                ];
            }

            // 2. Vérifier que le compte est de type épargne
            if ($compte->type !== 'epargne') {
                return [
                    'success' => false,
                    'message' => 'Seuls les comptes épargne peuvent être bloqués. Les comptes chèque ne peuvent pas être bloqués.',
                    'http_code' => 400
                ];
            }

            // 3. Vérifier si le compte est déjà bloqué
            if ($compte->statut === 'bloque') {
                return [
                    'success' => false,
                    'message' => 'Le compte est déjà bloqué',
                    'http_code' => 400
                ];
            }

            // 4. Vérifier si le compte a un blocage programmé en cours
            if ($compte->blocage_programme) {
                $dateBloqueProgramme = $compte->dateDebutBlocage 
                    ? \Carbon\Carbon::parse($compte->dateDebutBlocage)->format('d/m/Y')
                    : 'date non définie';
                    
                return [
                    'success' => false,
                    'message' => "Le compte a déjà un blocage programmé prévu le {$dateBloqueProgramme}",
                    'http_code' => 400
                ];
            }

            // 5. Vérifier que le compte est actif
            if ($compte->statut !== 'actif') {
                return [
                    'success' => false,
                    'message' => "Le compte ne peut pas être bloqué. Statut actuel : {$compte->statut}",
                    'http_code' => 400
                ];
            }

            // 6. Extraire les paramètres
            $dateDebutBlocage = isset($data['dateDebutBlocage'])
                ? \Carbon\Carbon::parse($data['dateDebutBlocage'])->startOfDay()
                : now()->startOfDay();
            
            $dateFinBlocage = isset($data['dateFinBlocage'])
                ? \Carbon\Carbon::parse($data['dateFinBlocage'])->startOfDay()
                : null;
                
            $motifBlocage = $data['raison'] ?? 'Blocage administratif';

            // 5. Vérifier si le blocage est immédiat ou programmé
            $aujourdhui = now()->startOfDay();
            
            if ($dateDebutBlocage->equalTo($aujourdhui)) {
                // BLOCAGE IMMÉDIAT → Archiver dans Neon
                
                // Mettre à jour le statut avant archivage
                $compte->update([
                    'statut' => 'bloque',
                    'motifBlocage' => $motifBlocage,
                    'dateDebutBlocage' => $dateDebutBlocage,
                    'dateFinBlocage' => $dateFinBlocage,
                    'dateBlocage' => now(),
                    'blocage_programme' => false,
                    'derniereModification' => now(),
                    'version' => $compte->version + 1,
                ]);

                // Archiver dans Neon
                $this->compteArchiveService->archiveCompte($compte, auth()->user(), $motifBlocage);
                
                // Supprimer de PostgreSQL (soft delete)
                $compte->delete();

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'Compte bloqué avec succès et archivé dans Neon',
                    'data' => [
                        'id' => $compte->id,
                        'numeroCompte' => $compte->numeroCompte,
                        'statut' => 'bloque',
                        'motifBlocage' => $motifBlocage,
                        'dateDebutBlocage' => $dateDebutBlocage->toIso8601String(),
                        'dateFinBlocage' => $dateFinBlocage?->toIso8601String(),
                        'dateBlocage' => now()->toIso8601String(),
                        'archived' => true,
                        'location' => 'Neon'
                    ]
                ];

            } else {
                // BLOCAGE PROGRAMMÉ → Reste dans PostgreSQL avec statut actif
                
                $compte->update([
                    'statut' => 'actif', // Reste actif
                    'motifBlocage' => $motifBlocage,
                    'dateDebutBlocage' => $dateDebutBlocage,
                    'dateFinBlocage' => $dateFinBlocage,
                    'dateBlocage' => null,
                    'blocage_programme' => true,
                    'derniereModification' => now(),
                    'version' => $compte->version + 1,
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'message' => "Ce compte sera bloqué le {$dateDebutBlocage->format('d/m/Y')}",
                    'data' => [
                        'id' => $compte->id,
                        'numeroCompte' => $compte->numeroCompte,
                        'statut' => 'actif',
                        'motifBlocage' => $motifBlocage,
                        'dateDebutBlocage' => $dateDebutBlocage->toIso8601String(),
                        'dateFinBlocage' => $dateFinBlocage?->toIso8601String(),
                        'blocage_programme' => true,
                        'location' => 'PostgreSQL'
                    ]
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Gérer l'erreur de duplicate key (compte déjà dans Neon)
            if (str_contains($e->getMessage(), 'duplicate key value violates unique constraint')) {
                return [
                    'success' => false,
                    'message' => 'Ce compte est déjà archivé dans la base Neon et ne peut pas être bloqué à nouveau',
                    'http_code' => 400
                ];
            }
            
            throw $e;
        }
    }

    /**
     * Débloquer un compte épargne (US 2.5)
     * - Seuls les comptes bloqués ou avec un blocage programmé peuvent être débloqués
     */
    public function debloquerCompte(string $compteId, array $data): array
    {
        DB::beginTransaction();

        try {
            // 1. Récupérer le compte
            $compte = Compte::find($compteId);

            if (!$compte) {
                return [
                    'success' => false,
                    'message' => "Le compte avec l'ID {$compteId} n'existe pas",
                    'http_code' => 404
                ];
            }

            // 2. Vérifier que le compte est bloqué ou a un blocage programmé
            if ($compte->statut !== 'bloque' && !$compte->blocage_programme) {
                return [
                    'success' => false,
                    'message' => "Le compte ne peut pas être débloqué. Statut actuel : {$compte->statut}",
                    'http_code' => 400
                ];
            }

            // 3. Débloquer le compte (annuler le blocage ou le blocage programmé)
            $compte->update([
                'statut' => 'actif',
                'motifBlocage' => null,
                'dateDebutBlocage' => null,
                'dateDeblocage' => now(),
                'dateBlocage' => null,
                'dateDeblocagePrevue' => null,
                'blocage_programme' => false,
                'derniereModification' => now(),
                'version' => $compte->version + 1,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Compte débloqué avec succès',
                'data' => [
                    'id' => $compte->id,
                    'statut' => $compte->statut,
                    'dateDeblocage' => $compte->dateDeblocage?->toIso8601String(),
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour les informations d'un compte (US 2.3)
     * 
     * @param string $compteId ID du compte à mettre à jour
     * @param array $data Données à mettre à jour
     * @return array
     */
    public function updateCompte(string $compteId, array $data): array
    {
        DB::beginTransaction();
        
        try {
            // Récupérer le compte avec ses relations
            $compte = Compte::with(['client.user'])->find($compteId);

            if (!$compte) {
                return [
                    'success' => false,
                    'message' => 'Compte non trouvé',
                    'http_code' => 404
                ];
            }

            // Vérifier que le compte n'est pas archivé
            if ($compte->archived_at) {
                return [
                    'success' => false,
                    'message' => 'Impossible de modifier un compte archivé',
                    'http_code' => 400
                ];
            }

            // Mettre à jour les informations du titulaire (User) si fournies
            $user = $compte->client->user;
            $userUpdated = false;
            
            if (isset($data['titulaire'])) {
                $user->nomComplet = $data['titulaire'];
                $userUpdated = true;
            }
            
            if (isset($data['email'])) {
                $user->email = $data['email'];
                $userUpdated = true;
            }
            
            if (isset($data['telephone'])) {
                $user->telephone = $data['telephone'];
                $userUpdated = true;
            }
            
            if (isset($data['adresse'])) {
                $user->adresse = $data['adresse'];
                $userUpdated = true;
            }

            if ($userUpdated) {
                $user->save();
            }

            // Mettre à jour les informations du compte si fournies
            $compteUpdated = false;
            
            if (isset($data['type'])) {
                $compte->type = $data['type'];
                $compteUpdated = true;
            }
            
            if (isset($data['devise'])) {
                $compte->devise = $data['devise'];
                $compteUpdated = true;
            }

            if ($compteUpdated) {
                $compte->version = $compte->version + 1;
                $compte->save();
            }

            DB::commit();

            // Recharger le compte avec toutes les relations
            $compte->refresh();
            $compte->load(['client.user', 'transactions']);

            return [
                'success' => true,
                'message' => 'Compte mis à jour avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'titulaire' => $compte->client->user->nomComplet,
                    'type' => $compte->type,
                    'solde' => $compte->solde,
                    'devise' => $compte->devise,
                    'dateCreation' => $compte->dateCreation?->toIso8601String(),
                    'statut' => $compte->statut,
                    'metadata' => [
                        'derniereModification' => now()->toIso8601String(),
                        'version' => $compte->version,
                    ]
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
