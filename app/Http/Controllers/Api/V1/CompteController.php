<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Http\Requests\ListCompteRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Services\CompteService;
use App\Services\CompteArchiveService;
use App\Traits\ApiResponseFormat;
use App\Traits\Cacheable;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompteController extends Controller
{
    use ApiResponseFormat;

    protected CompteService $compteService;
    protected CompteArchiveService $archiveService;

    public function __construct(CompteService $compteService, CompteArchiveService $archiveService)
    {
        $this->compteService = $compteService;
        $this->archiveService = $archiveService;
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes",
     *     summary="Lister les comptes actifs",
     *     description="Récupère la liste des comptes ACTIFS non archivés avec pagination et filtres optionnels. Les administrateurs voient tous les comptes actifs, les clients ne voient que leurs propres comptes actifs. NOTE: Seuls les comptes avec statut 'actif' sont retournés - les comptes bloqués et fermés sont exclus.",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page pour la pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (maximum 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100, example=10)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"}, example="epargne")
     *     ),
     *     @OA\Parameter(
     *         name="devise",
     *         in="query",
     *         description="Filtrer par devise",
     *         required=false,
     *         @OA\Schema(type="string", example="FCFA")
     *     ),
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="query",
     *         description="Filtrer par numéro de compte exact (format: CPxxxxxxxxxx)",
     *         required=false,
     *         @OA\Schema(type="string", pattern="^CP\d{10}$", example="CP3385015606")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Rechercher par nom du titulaire ou numéro de compte",
     *         required=false,
     *         @OA\Schema(type="string", example="Diop")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "derniereModification", "numeroCompte"}, default="dateCreation", example="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc", example="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="a032f0ea-25e7-4b17-a7c4-e0a1aa6aa289"),
     *                     @OA\Property(property="numeroCompte", type="string", example="CP3105472638"),
     *                     @OA\Property(property="titulaire", type="string", example="Mamadou Diop"),
     *                     @OA\Property(property="type", type="string", example="epargne"),
     *                     @OA\Property(property="solde", type="number", example=150000),
     *                     @OA\Property(property="devise", type="string", example="FCFA")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(property="count", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total_pages", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422, 
     *         description="Erreur de validation - Paramètres invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="limit",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le limit ne peut pas dépasser 100")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(ListCompteRequest $request): JsonResponse
    {
        // Déléguer toute la logique au service
        $response = $this->compteService->getComptesList($request);
        
        // Retourner la réponse
        return response()->json($response);
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/{id}",
     *     summary="Récupérer un compte spécifique par ID (US 2.1)",
     *     description="Récupère les détails complets d'un compte bancaire par son ID UUID. Implémente une stratégie de recherche dual-database : cherche d'abord dans PostgreSQL (tous les comptes : actifs, bloqués, fermés), puis dans Neon (comptes archivés) si non trouvé. Admin peut récupérer n'importe quel compte. Client peut récupérer uniquement ses propres comptes.",
     *     operationId="getCompteById",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès (depuis PostgreSQL ou Neon)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", nullable=true, example="Inactivité de 30+ jours"),
     *                 @OA\Property(
     *                     property="metadata",
     *                     type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1),
     *                     @OA\Property(property="archived", type="boolean", example=false, description="true si récupéré depuis Neon, false si depuis PostgreSQL")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé - Client tentant d'accéder à un compte qui ne lui appartient pas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="code", type="string", example="ACCESS_DENIED"),
     *                 @OA\Property(property="message", type="string", example="Accès non autorisé à ce compte"),
     *                 @OA\Property(
     *                     property="details",
     *                     type="object",
     *                     @OA\Property(property="compteId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé dans PostgreSQL ni dans Neon",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(
     *                     property="details",
     *                     type="object",
     *                     @OA\Property(property="compteId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors de la récupération du compte")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = auth()->user();
            
            // Déléguer la logique au service
            $result = $this->compteService->getCompteById($id, $user);

            // Gérer les erreurs
            if (!$result['success']) {
                return response()->json($result, $result['http_code'] ?? 500);
            }

            // Succès - retourner les données
            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Erreur lors de la récupération du compte : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la récupération du compte'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/numero/{numero}",
     *     summary="Obtenir un compte par numéro",
     *     description="Récupère les détails complets d'un compte bancaire en utilisant son numéro de compte. Cherche automatiquement dans la base principale (Render) et dans les archives (Neon) si le compte est fermé, bloqué ou archivé.",
     *     operationId="getCompteByNumero",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte (format: CPxxxxxxxxxx)",
     *         required=true,
     *         @OA\Schema(type="string", example="CP3105472638")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte récupéré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="a032f0ea-25e7-4b17-a7c4-e0a1aa6aa289"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP3105472638"),
     *                 @OA\Property(property="titulaire", type="string", example="Mamadou Diop"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=150000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="archived", type="boolean", example=false, description="Indique si le compte est archivé dans Neon")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404, 
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé"),
     *             @OA\Property(property="error", type="string", example="Le compte avec le numéro CP9999999999 n'existe pas")
     *         )
     *     )
     * )
     */
    public function showByNumero(string $numero): JsonResponse
    {
        try {
            // 1. Chercher d'abord dans la base principale (Render) - comptes actifs uniquement
            $compte = Compte::where('numeroCompte', $numero)
                ->whereNull('archived_at')
                ->where('statut', 'actif')
                ->with(['client.user'])
                ->first();

            if ($compte) {
                // Compte actif trouvé dans la base principale
                return $this->success(
                    new CompteResource($compte),
                    'Compte actif récupéré avec succès'
                );
            }

            // 2. Si non trouvé ou archivé, chercher dans Neon (comptes fermés/bloqués/archivés)
            $archived = $this->archiveService->getArchivedCompte($numero);

            if ($archived) {
                // Compte trouvé dans les archives Neon
                return $this->success(
                    [
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
                    'Compte archivé récupéré depuis Neon'
                );
            }

            // 3. Compte introuvable dans les deux bases
            return $this->notFound(
                "Le compte avec le numéro {$numero} n'existe pas"
            );

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Erreur lors de la récupération du compte : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la récupération du compte'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes",
     *     summary="Créer un nouveau compte bancaire",
     *     description="Crée un nouveau compte bancaire avec validation complète (NCI, téléphone, email). Le mot de passe est généré automatiquement et envoyé par email.",
     *     tags={"Comptes"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du compte à créer",
     *         @OA\JsonContent(
     *             required={"type", "devise", "client"},
     *             @OA\Property(
     *                 property="type", 
     *                 type="string", 
     *                 enum={"epargne", "cheque"}, 
     *                 description="Type de compte (epargne ou cheque uniquement)",
     *                 example="epargne"
     *             ),
     *             @OA\Property(
     *                 property="devise", 
     *                 type="string", 
     *                 enum={"FCFA", "USD", "EUR"}, 
     *                 description="Devise du compte",
     *                 example="FCFA"
     *             ),
     *             @OA\Property(
     *                 property="client",
     *                 type="object",
     *                 description="Informations du client",
     *                 required={"titulaire", "nci", "email", "telephone", "adresse"},
     *                 @OA\Property(property="id", type="string", nullable=true, description="ID du client existant (optionnel)", example=null),
     *                 @OA\Property(property="titulaire", type="string", description="Nom complet du titulaire", example="Mamadou Diop"),
     *                 @OA\Property(property="nci", type="string", description="Numéro NCI sénégalais (13 chiffres commençant par 1 ou 2)", example="1234567890123"),
     *                 @OA\Property(property="email", type="string", format="email", description="Adresse email unique", example="mamadou.diop@example.com"),
     *                 @OA\Property(property="telephone", type="string", description="Téléphone sénégalais (+221 suivi de 70/75/76/77/78)", example="+221771234567"),
     *                 @OA\Property(property="adresse", type="string", description="Adresse complète", example="Dakar, Plateau")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="a032f0ea-25e7-4b17-a7c4-e0a1aa6aa289"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP3105472638"),
     *                 @OA\Property(property="titulaire", type="string", example="Mamadou Diop"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=0),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-25T15:35:06+00:00"),
     *                 @OA\Property(property="statut", type="string", example="actif")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation - Données invalides ou champs requis manquants",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 description="Exemples de tous les cas d'erreur possibles. Chaque champ peut avoir plusieurs types d'erreurs (requis, format, unicité).",
     *                 @OA\Property(
     *                     property="type",
     *                     type="array",
     *                     description="Erreurs possibles : champ manquant, valeur invalide",
     *                     @OA\Items(type="string", example="Le type de compte est requis")
     *                 ),
     *                 @OA\Property(
     *                     property="devise",
     *                     type="array",
     *                     description="Erreurs possibles : champ manquant, valeur invalide",
     *                     @OA\Items(type="string", example="La devise est requise")
     *                 ),
     *                 @OA\Property(
     *                     property="client",
     *                     type="array",
     *                     description="Erreur si l'objet client est manquant",
     *                     @OA\Items(type="string", example="Les informations du client sont requises")
     *                 ),
     *                 @OA\Property(
     *                     property="client.titulaire",
     *                     type="array",
     *                     description="Erreur : champ manquant",
     *                     @OA\Items(type="string", example="Le nom du titulaire est requis")
     *                 ),
     *                 @OA\Property(
     *                     property="client.nci",
     *                     type="array",
     *                     description="Erreurs possibles : 1) champ manquant 'Le NCI est requis', 2) format invalide 'Le NCI doit être un numéro NCI sénégalais valide (13 chiffres commençant par 1 ou 2)', 3) déjà utilisé 'Ce NCI est déjà utilisé'",
     *                     @OA\Items(type="string", example="Ce NCI est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.email",
     *                     type="array",
     *                     description="Erreurs possibles : 1) champ manquant 'L'email est requis', 2) format invalide 'L'email doit être valide', 3) déjà utilisé 'Cet email est déjà utilisé'",
     *                     @OA\Items(type="string", example="Cet email est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.telephone",
     *                     type="array",
     *                     description="Erreurs possibles : 1) champ manquant 'Le téléphone est requis', 2) format invalide 'Le téléphone doit être un numéro de téléphone sénégalais valide (+221 suivi de 70/75/76/77/78)', 3) déjà utilisé 'Ce numéro de téléphone est déjà utilisé'",
     *                     @OA\Items(type="string", example="Ce numéro de téléphone est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.adresse",
     *                     type="array",
     *                     description="Erreur : champ manquant",
     *                     @OA\Items(type="string", example="L'adresse est requise")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de format - NCI ou téléphone invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="client.nci",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le NCI doit être un numéro NCI sénégalais valide (13 chiffres commençant par 1 ou 2)")
     *                 ),
     *                 @OA\Property(
     *                     property="client.telephone",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le téléphone doit être un numéro de téléphone sénégalais valide (+221 suivi de 70/75/76/77/78)")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur interne",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue : [détails de l'erreur]")
     *         )
     *     )
     * )
     */
    public function store(StoreCompteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $password = null;
            $code = null;

            // 1. Vérifier l'existence du client
            if (!empty($request->client['id'])) {
                $client = Client::findOrFail($request->client['id']);
            } else {
                // 2. Créer l'utilisateur et le client s'il n'existe pas
                $password = Client::generatePassword();
                $code = Client::generateCode();

                // Créer l'utilisateur
                $user = User::create([
                    'nomComplet' => $request->client['titulaire'],
                    'nci' => $request->client['nci'],
                    'email' => $request->client['email'],
                    'telephone' => $request->client['telephone'],
                    'adresse' => $request->client['adresse'],
                    'password' => Hash::make($password),
                    'code' => $code,
                ]);

                // Créer le client
                $client = Client::create([
                    'user_id' => $user->id,
                ]);

                // Stocker temporairement pour l'observer
                session([
                    'temp_client_password' => $password,
                    'temp_client_code' => $code,
                ]);
            }

            // 3. Créer le compte (le numéro sera généré automatiquement par CompteObserver)
            $compte = Compte::create([
                'type' => $request->type,
                'devise' => $request->devise,
                'statut' => 'actif',
                'client_id' => $client->id,
            ]);

            // Charger les relations
            $compte->load(['client.user', 'transactions']);

            // Invalider le cache de la liste des comptes
            $this->forgetPaginatedCache('comptes:list');

            DB::commit();

            // Utiliser le trait pour formater la réponse
            return $this->created([
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
            ], 'Compte créé avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->validationError($e->errors(), 'Les données fournies sont invalides');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la création du compte'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/archives",
     *     summary="Lister les comptes archivés",
     *     description="Récupère la liste des comptes épargne archivés depuis le cloud (Neon). Les administrateurs voient tous les comptes archivés, les clients ne voient que leurs propres comptes archivés.",
     *     operationId="getArchivedComptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes archivés récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Comptes archivés récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="numeroCompte", type="string"),
     *                     @OA\Property(property="type", type="string", example="epargne"),
     *                     @OA\Property(property="solde", type="number", format="float"),
     *                     @OA\Property(property="archived_at", type="string", format="date-time"),
     *                     @OA\Property(property="archive_reason", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function archives(): JsonResponse
    {
        try {
            // Récupérer tous les comptes archivés (sans restriction)
            $archives = $this->archiveService->getAllArchivedComptes();

            return $this->success(
                $archives,
                'Liste de tous les comptes archivés récupérée avec succès'
            );

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la récupération des comptes archivés'
            );
        }
    }

    /**
     * Méthode archive() - Utilisée uniquement en interne par les Jobs automatiques
     * Pas d'endpoint public exposé dans les routes
     */
    public function archive(string $numeroCompte): JsonResponse
    {
        try {
            $compte = Compte::where('numeroCompte', $numeroCompte)->first();

            if (!$compte) {
                throw new CompteNotFoundException("Le compte {$numeroCompte} n'existe pas");
            }

            // Archiver vers Neon (sans vérification de rôle)
            $reason = request()->input('reason');
            $archive = $this->archiveService->archiveCompte($compte, null, $reason);

            return $this->success([
                'numeroCompte' => $compte->numeroCompte,
                'archived_at' => $archive->archived_at,
                'archive_reason' => $archive->archive_reason,
            ], 'Compte archivé avec succès dans le cloud');

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de l\'archivage du compte'
            );
        }
    }

    /**
     * @OA\Patch(
     *     path="/v1/comptes/{compteId}",
     *     summary="Mettre à jour un compte (US 2.3)",
     *     description="Permet à un administrateur de mettre à jour les informations d'un compte bancaire. Seuls les administrateurs peuvent utiliser cet endpoint.",
     *     operationId="updateCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="UUID du compte à mettre à jour",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="a032f0ea-25e7-4b17-a7c4-e0a1aa6aa289")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données à mettre à jour (au moins un champ requis)",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="cheque"),
     *             @OA\Property(property="solde", type="number", format="float", example=50000),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "USD", "EUR"}, example="FCFA")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numeroCompte", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="solde", type="number"),
     *                 @OA\Property(property="statut", type="string"),
     *                 @OA\Property(property="devise", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé - Admin uniquement"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function update(string $compteId): JsonResponse
    {
        try {
            $data = request()->validate([
                'type' => 'sometimes|in:epargne,cheque',
                'solde' => 'sometimes|numeric|min:0',
                'statut' => 'sometimes|in:actif,bloque,ferme',
                'devise' => 'sometimes|in:FCFA,USD,EUR',
            ]);

            $result = $this->compteService->updateCompte($compteId, $data);

            return $this->success($result, 'Compte mis à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la mise à jour du compte'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes/{compteId}/bloquer",
     *     summary="Bloquer un compte (US 2.5)",
     *     description="Bloque un compte bancaire immédiatement ou de manière programmée. L'archivage automatique se fait via ArchiveComptesBloquesJob lorsque dateDebutBlocage arrive.",
     *     operationId="bloquerCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="UUID du compte à bloquer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="dateDebutBlocage", type="string", format="date", example="2025-11-01", description="Date de début du blocage (optionnel, immédiat si absent)"),
     *             @OA\Property(property="raison", type="string", example="Blocage administratif")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="dateDebutBlocage", type="string", nullable=true),
     *                 @OA\Property(property="blocage_programme", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function bloquer(string $compteId): JsonResponse
    {
        try {
            $data = request()->validate([
                'dateDebutBlocage' => 'nullable|date|after_or_equal:today',
                'raison' => 'nullable|string|max:500',
            ]);

            $result = $this->compteService->bloquerCompte($compteId, $data);

            return $this->success($result, 'Compte bloqué avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors du blocage du compte'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes/{compteId}/debloquer",
     *     summary="Débloquer un compte (US 2.5)",
     *     description="Débloque un compte bancaire immédiatement ou de manière programmée. Le désarchivage automatique se fait via DearchiveComptesBloquesJob lorsque dateDeblocagePrevue arrive.",
     *     operationId="debloquerCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="UUID du compte à débloquer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="dateDeblocagePrevue", type="string", format="date", example="2025-12-01", description="Date de déblocage programmé (optionnel, immédiat si absent)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte débloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte débloqué avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="dateDeblocagePrevue", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function debloquer(string $compteId): JsonResponse
    {
        try {
            $data = request()->validate([
                'dateDeblocagePrevue' => 'nullable|date|after_or_equal:today',
            ]);

            $result = $this->compteService->debloquerCompte($compteId, $data);

            return $this->success($result, 'Compte débloqué avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors du déblocage du compte'
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1/comptes/{numeroCompte}",
     *     summary="Supprimer un compte (US 2.4)",
     *     description="Supprime (soft delete) un compte épargne et l'archive automatiquement dans Neon. Seuls les comptes épargne peuvent être supprimés.",
     *     operationId="deleteCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         description="Numéro du compte à supprimer",
     *         required=true,
     *         @OA\Schema(type="string", example="CP3105472638")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé et archivé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé et archivé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="numeroCompte", type="string"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time"),
     *                 @OA\Property(property="archived_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Seuls les comptes épargne peuvent être supprimés"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function destroy(string $numeroCompte): JsonResponse
    {
        try {
            $result = $this->compteService->deleteAndArchive($numeroCompte);

            return $this->success($result, 'Compte supprimé et archivé avec succès');

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la suppression du compte'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes/restore/{id}",
     *     summary="Restaurer un compte supprimé",
     *     description="Restaure un compte précédemment supprimé depuis les archives Neon vers la base principale.",
     *     operationId="restoreCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du compte à restaurer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte restauré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte restauré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="numeroCompte", type="string"),
     *                 @OA\Property(property="restored_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé dans les archives")
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $result = $this->compteService->restore($id);

            return $this->success($result, 'Compte restauré avec succès');

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la restauration du compte'
            );
        }
    }
}
