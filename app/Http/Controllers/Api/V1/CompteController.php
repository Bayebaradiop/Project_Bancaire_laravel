<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Http\Requests\ListCompteRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\BloquerCompteRequest;
use App\Http\Requests\DebloquerCompteRequest;
use App\Services\CompteService;
use App\Repositories\CompteRepository;
use App\Traits\ApiResponseFormat;
use App\Traits\Cacheable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompteController extends Controller
{
    use ApiResponseFormat, Cacheable;

    protected CompteService $compteService;
    protected CompteRepository $compteRepository;

    public function __construct(CompteService $compteService, CompteRepository $compteRepository)
    {
        $this->compteService = $compteService;
        $this->compteRepository = $compteRepository;
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes",
     *     summary="Lister les comptes",
     *     description="Récupère la liste des comptes avec pagination et filtres optionnels.

**Autorisations :**
- **Admin** : Voit tous les comptes de tous les clients
- **Client** : Voit uniquement ses propres comptes

**Pour tester en tant que Admin :**
- Email : `admin@banque.sn` / Password : `password`

**Pour tester en tant que Client :**
- Email : `client@banque.sn` / Password : `password`

**📝 Instructions :** Après connexion, copiez le token et cliquez sur **Authorize** en haut à droite pour le coller dans **bearerAuth (http, Bearer)**.",
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
        // Récupérer l'utilisateur authentifié
        $user = $request->user();
        
        // Déléguer toute la logique au service avec autorisation
        $response = $this->compteService->getComptesList($request, $user);
        
        // Retourner la réponse
        return response()->json($response);
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/{id}",
     *     summary="Récupérer un compte spécifique par ID (US 2.1)",
     *     description="Récupère les détails complets d'un compte bancaire par son ID UUID. Implémente une stratégie de recherche dual-database : cherche d'abord dans PostgreSQL (comptes actifs), puis dans Neon (comptes archivés) si non trouvé.

**Autorisations :**
- **Admin** : Peut récupérer n'importe quel compte
- **Client** : Peut récupérer uniquement ses propres comptes

**Comptes du client test (`client@banque.sn`) :**
- `a0358125-5167-4b7c-8057-786038cd1e84`
- `a0358113-ee00-4154-884d-9a3bd5d307fc`
- `a0358111-2230-42ff-94d4-a6db429db2c1`

**📝 Instructions :** Connectez-vous, copiez le token, cliquez sur **Authorize** en haut à droite et collez-le dans **bearerAuth (http, Bearer)**.",
     *     operationId="getCompteById",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID UUID du compte. Exemples de comptes existants : a0358129-098e-46e8-99c7-be73a3943006",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="a0358129-098e-46e8-99c7-be73a3943006")
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
     *     description="Récupère les détails complets d'un compte bancaire en utilisant son numéro de compte. Cherche automatiquement dans la base principale (Render) et dans les archives (Neon) si le compte est fermé, bloqué ou archivé.
     * 
     * **Contrôles d'accès:**
     * - **Admin**: Peut voir tous les comptes (actifs et archivés)
     * - **Client**: Ne peut voir que ses propres comptes (retourne 403 Forbidden pour les comptes d'autres clients)
     * 
     * **Exemple de numéro de compte:** CP6617828903",
     *     operationId="getCompteByNumero",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte (format: CPxxxxxxxxxx)",
     *         required=true,
     *         @OA\Schema(type="string", example="CP6617828903")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte actif récupéré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="a03747b4-b6cf-4216-878f-a250ddf44a6a"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP6617828903"),
     *                 @OA\Property(property="titulaire", type="string", example="Moussa Ndiaye Test"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=0),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="archived", type="boolean", example=false, description="Indique si le compte est archivé dans Neon")
             )
         )
     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé - Le client tente d'accéder au compte d'un autre client",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé à ce compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404, 
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte avec le numéro CP9999999999 n'existe pas")
         )
     ),
     *     @OA\Response(
     *         response=410,
     *         description="Compte archivé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte CP6991885910 est archivé et n'est plus actif. Consultez /api/v1/comptes/archives pour plus de détails.")
     *         )
     *     )
     * )
     */
    public function showByNumero(string $numero): JsonResponse
    {
        try {
            $user = auth()->user();
            $result = $this->compteService->getCompteByNumero($numero, $user);

            // Gérer les erreurs
            if (isset($result['error'])) {
                return match($result['code']) {
                    403 => $this->error($result['message'], 403),
                    404 => $this->notFound($result['message']),
                    default => $this->serverError($result['message'])
                };
            }

            // Succès
            return $this->success($result['data'], $result['message']);

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
     *     security={{"bearerAuth": {}}},
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
            $result = $this->compteService->createCompte($request->validated());

            // Invalider le cache de la liste des comptes
            $this->forgetPaginatedCache('comptes:list');

            return $this->created($result['data'], $result['message']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Les données fournies sont invalides');

        } catch (\Exception $e) {
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
     *     summary="Lister les comptes archivés dans Neon",
     *     description="Récupère la liste complète des comptes archivés depuis la base de données Neon. Affiche tous les types de comptes (épargne, chèque, etc.) qui ont été supprimés et archivés. Les comptes sont triés par date de fermeture (du plus récent au plus ancien). Seuls les administrateurs peuvent accéder aux archives.",
     *     operationId="getArchivedComptes",
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
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes archivés récupérée avec succès depuis Neon",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="a035d839-2e9e-4aed-886e-2398a1ffd2f0"),
     *                     @OA\Property(property="numeroCompte", type="string", example="CP3162783468"),
     *                     @OA\Property(property="client_id", type="string", format="uuid", example="a035d836-d64e-439e-b997-16dffd01552b"),
     *                     @OA\Property(property="type", type="string", example="epargne"),
     *                     @OA\Property(property="solde", type="string", example="0.00"),
     *                     @OA\Property(property="devise", type="string", example="FCFA"),
     *                     @OA\Property(property="statut", type="string", example="ferme"),
     *                     @OA\Property(property="dateFermeture", type="string", format="date-time", example="2025-10-27 09:15:18"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-27 09:15:18"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-27 09:15:18")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="currentPage", type="integer", example=1),
     *                 @OA\Property(property="totalPages", type="integer", example=1),
     *                 @OA\Property(property="totalItems", type="integer", example=3),
     *                 @OA\Property(property="itemsPerPage", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Action non autorisée - Seuls les administrateurs peuvent consulter les archives",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Action non autorisée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors de la récupération des archives")
     *         )
     *     )
     * )
     */
    public function archives(): JsonResponse
    {
        try {
            // Récupérer tous les comptes archivés depuis Neon
            $result = $this->compteService->getArchived();

            return response()->json($result);

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug')
                    ? 'Une erreur est survenue : ' . $e->getMessage()
                    : 'Une erreur est survenue lors de la récupération des comptes archivés'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes/{numeroCompte}/archive",
     *     summary="Archiver un compte épargne",
     *     description="Archive un compte épargne vers le cloud (Neon). Seuls les administrateurs peuvent archiver des comptes.",
     *     operationId="archiveCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         description="Numéro du compte à archiver",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="reason", type="string", example="Inactif depuis 12 mois")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte archivé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Compte archivé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="archived_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Non autorisé - seuls les administrateurs peuvent archiver",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Seuls les administrateurs peuvent archiver des comptes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
     *         )
     *     )
     * )
     */
    public function archive(string $numeroCompte, Request $request): JsonResponse
    {
        try {
            $result = $this->compteService->deleteAndArchive($numeroCompte);

            // Gérer les erreurs
            if (!$result['success']) {
                return response()->json($result, $result['code'] ?? 400);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug')
                    ? 'Une erreur est survenue : ' . $e->getMessage()
                    : 'Une erreur est survenue lors de la suppression du compte'
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1/comptes/{numeroCompte}",
     *     summary="Supprimer un compte et l'archiver dans Neon",
     *     description="Supprime définitivement un compte bancaire de la base principale (PostgreSQL) et l'archive automatiquement dans la base Neon. Cette opération est irréversible depuis PostgreSQL mais le compte peut être restauré depuis l'archive Neon. Seuls les administrateurs peuvent supprimer des comptes.",
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
     *         description="Compte supprimé avec succès et archivé dans Neon",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès et archivé dans Neon"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="a035d140-7bf1-45cd-b5dd-5401faeda695"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP5091523552"),
     *                 @OA\Property(property="statut", type="string", example="ferme"),
     *                 @OA\Property(property="dateFermeture", type="string", format="date-time", example="2025-10-27T09:21:58+00:00")
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
     *         description="Action non autorisée - Seuls les administrateurs peuvent supprimer des comptes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Action non autorisée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte CP9999999999 n'existe pas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Compte déjà supprimé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte CP3105472638 est déjà supprimé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors de la suppression du compte")
     *         )
     *     )
     * )
     */
    public function destroy(string $numeroCompte): JsonResponse
    {
        return $this->archive($numeroCompte, request());
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes/restore/{id}",
     *     summary="Restaurer un compte archivé depuis Neon",
     *     description="Restaure un compte bancaire précédemment supprimé et archivé dans Neon. Le compte est recréé dans PostgreSQL avec le statut 'actif' et supprimé de l'archive Neon. Seuls les administrateurs peuvent restaurer des comptes.",
     *     operationId="restoreCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID UUID du compte archivé à restaurer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="a035d140-7bf1-45cd-b5dd-5401faeda695")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte restauré avec succès depuis l'archive Neon",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte restauré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="a0367188-21dd-4f37-9d55-c5cf90850058"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP5091523552"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="type", type="string", example="cheque"),
     *                 @OA\Property(property="titulaire", type="string", example="Baye Bara Diop"),
     *                 @OA\Property(property="solde", type="number", example=0),
     *                 @OA\Property(property="devise", type="string", example="FCFA")
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
     *         description="Action non autorisée - Seuls les administrateurs peuvent restaurer des comptes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Action non autorisée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé dans les archives",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte avec l'ID a035d140-7bf1-45cd-b5dd-5401faeda695 n'existe pas dans les archives"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors de la restauration du compte")
     *         )
     *     )
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $result = $this->compteService->restore($id);

            // Gérer les erreurs
            if (!$result['success']) {
                return response()->json($result, $result['code'] ?? 400);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug')
                    ? 'Une erreur est survenue : ' . $e->getMessage()
                    : 'Une erreur est survenue lors de la restauration du compte'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes/{compteId}/bloquer",
     *     summary="Bloquer un compte épargne (US 2.5)",
     *     description="Bloque un compte épargne actif pour une durée déterminée. Seuls les administrateurs peuvent bloquer un compte. Le compte doit être de type épargne et avoir un statut actif. La date de déblocage prévue est calculée automatiquement.

**Note pour les tests :** L'ID d'exemple `a0358129-098e-46e8-99c7-be73a3943006` existe dans la base de données Render et peut être utilisé pour vos tests. Vous pouvez aussi récupérer d'autres IDs via GET /v1/comptes.",
     *     operationId="bloquerCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID UUID du compte à bloquer. Exemple: a0358129-098e-46e8-99c7-be73a3943006 (existe dans la base Render pour les tests)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="a0358129-098e-46e8-99c7-be73a3943006")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informations de blocage du compte",
     *         @OA\JsonContent(
     *             required={"motif", "duree", "unite"},
     *             @OA\Property(property="motif", type="string", minLength=10, maxLength=500, example="Activité suspecte détectée", description="Motif du blocage (minimum 10 caractères)"),
     *             @OA\Property(property="duree", type="integer", minimum=1, maximum=365, example=30, description="Durée du blocage"),
     *             @OA\Property(property="unite", type="string", enum={"jours", "mois"}, example="mois", description="Unité de temps (jours ou mois)")
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
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Activité suspecte détectée"),
     *                 @OA\Property(property="dateBlocage", type="string", format="date-time", example="2025-10-19T11:20:00Z"),
     *                 @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", example="2025-11-18T11:20:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou compte non éligible au blocage",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seuls les comptes épargne peuvent être bloqués")
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
     *         description="Action non autorisée - Seuls les administrateurs peuvent bloquer un compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Action non autorisée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte avec l'ID 550e8400-e29b-41d4-a716-446655440000 n'existe pas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des données",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="motif", type="array", @OA\Items(type="string", example="Le motif de blocage est obligatoire")),
     *                 @OA\Property(property="duree", type="array", @OA\Items(type="string", example="La durée de blocage est obligatoire"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors du blocage du compte")
     *         )
     *     )
     * )
     */
    public function bloquer(string $compteId, BloquerCompteRequest $request): JsonResponse
    {
        try {
            // Déléguer la logique au service
            $result = $this->compteService->bloquerCompte($compteId, $request->validated());

            // Gérer les erreurs
            if (!$result['success']) {
                return response()->json($result, $result['http_code'] ?? 400);
            }

            // Succès
            return response()->json($result);

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
     *     summary="Débloquer un compte épargne (US 2.5)",
     *     description="Débloque un compte épargne précédemment bloqué et le remet en statut actif. Seuls les administrateurs peuvent débloquer un compte. Le compte doit avoir un statut bloqué.

**Note pour les tests :** L'ID d'exemple `a0358129-098e-46e8-99c7-be73a3943006` existe dans la base de données Render et peut être utilisé pour vos tests. Vous pouvez aussi récupérer d'autres IDs via GET /v1/comptes.",
     *     operationId="debloquerCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID UUID du compte à débloquer. Exemple: a0358129-098e-46e8-99c7-be73a3943006 (existe dans la base Render pour les tests)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="a0358129-098e-46e8-99c7-be73a3943006")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Motif du déblocage",
     *         @OA\JsonContent(
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string", minLength=10, maxLength=500, example="Vérification complétée", description="Motif du déblocage (minimum 10 caractères)")
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
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="dateDeblocage", type="string", format="date-time", example="2025-10-19T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Compte non éligible au déblocage",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte ne peut pas être débloqué. Statut actuel : actif")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Action non autorisée - Seuls les administrateurs peuvent débloquer un compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Action non autorisée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte avec l'ID 550e8400-e29b-41d4-a716-446655440000 n'existe pas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des données",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="motif", type="array", @OA\Items(type="string", example="Le motif de déblocage est obligatoire"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors du déblocage du compte")
     *         )
     *     )
     * )
     */
    public function debloquer(string $compteId, DebloquerCompteRequest $request): JsonResponse
    {
        try {
            // Déléguer la logique au service
            $result = $this->compteService->debloquerCompte($compteId, $request->validated());

            // Gérer les erreurs
            if (!$result['success']) {
                return response()->json($result, $result['http_code'] ?? 400);
            }

            // Succès
            return response()->json($result);

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug')
                    ? 'Une erreur est survenue : ' . $e->getMessage()
                    : 'Une erreur est survenue lors du déblocage du compte'
            );
        }
    }
}
