<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Http\Requests\ListCompteRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Traits\ApiResponseFormat;
use App\Traits\Cacheable;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompteController extends Controller
{
    use ApiResponseFormat, Cacheable;

    /**
     * @OA\Get(
     *     path="/v1/comptes",
     *     summary="Lister tous les comptes",
     *     description="Récupère la liste de tous les comptes avec pagination et filtres optionnels",
     *     operationId="getComptes",
     *     tags={"Comptes"},
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
        // Les paramètres sont déjà validés par ListCompteRequest
        $limit = $request->getLimit();
        $type = $request->getType();
        $statut = $request->getStatut();
        $search = $request->getSearch();
        $sort = $request->getSort();
        $order = $request->getOrder();
        $page = $request->input('page', 1);

        // Clé de cache basée sur les paramètres
        $cacheKey = "comptes:list:{$type}:{$statut}:{$search}:{$sort}:{$order}";

        // Utiliser le cache avec pagination (5 minutes)
        $comptes = $this->rememberPaginated($cacheKey, $page, $limit, function () use ($type, $statut, $search, $sort, $order, $limit) {
            // Construction de la requête
            $query = Compte::with(['client.user']);

            // Appliquer les filtres
            if ($type) {
                $query->type($type);
            }

            if ($statut) {
                $query->statut($statut);
            }

            if ($search) {
                $query->search($search);
            }

            // Appliquer le tri
            $query->sortBy($sort, $order);

            // Pagination
            return $query->paginate($limit);
        }, 300); // Cache pendant 5 minutes

        // Formater la réponse
        $data = CompteResource::collection($comptes);

        return $this->paginated(
            $data,
            $comptes,
            '/api/v1/comptes',
            'Liste des comptes récupérée avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/numero/{numero}",
     *     summary="Obtenir un compte par numéro",
     *     description="Récupère les détails complets d'un compte bancaire en utilisant son numéro de compte",
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
     *                 @OA\Property(property="statut", type="string", example="actif")
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
    public function showByNumero(Compte $compte): JsonResponse
    {
        // Le compte est automatiquement chargé via Route Model Binding
        // avec les relations (client.user, transactions) grâce à resolveRouteBinding()
        // Si le compte n'existe pas, Laravel retourne automatiquement une 404
        
        // Utiliser le cache pour 10 minutes
        $cacheKey = "compte:numero:{$compte->numeroCompte}";
        
        $cachedCompte = $this->remember($cacheKey, function () use ($compte) {
            return $compte;
        }, 600);

        return $this->success(
            new CompteResource($cachedCompte),
            'Compte récupéré avec succès'
        );
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
     *                 description="Exemples de tous les cas d'erreur possibles",
     *                 @OA\Property(
     *                     property="type",
     *                     type="array",
     *                     description="Erreurs sur le champ type",
     *                     @OA\Items(type="string", example="Le champ type est obligatoire.")
     *                 ),
     *                 @OA\Property(
     *                     property="devise",
     *                     type="array",
     *                     description="Erreurs sur le champ devise",
     *                     @OA\Items(type="string", example="Le champ devise est obligatoire.")
     *                 ),
     *                 @OA\Property(
     *                     property="client",
     *                     type="array",
     *                     description="Erreur si l'objet client est manquant",
     *                     @OA\Items(type="string", example="Le champ client est obligatoire.")
     *                 ),
     *                 @OA\Property(
     *                     property="client.titulaire",
     *                     type="array",
     *                     description="Erreurs sur le nom du titulaire",
     *                     @OA\Items(type="string", example="Le champ client.titulaire est obligatoire.")
     *                 ),
     *                 @OA\Property(
     *                     property="client.nci",
     *                     type="array",
     *                     description="Erreurs sur le NCI (requis, format, unicité)",
     *                     @OA\Items(type="string", example="Ce NCI est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.email",
     *                     type="array",
     *                     description="Erreurs sur l'email (requis, format, unicité)",
     *                     @OA\Items(type="string", example="Cet email est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.telephone",
     *                     type="array",
     *                     description="Erreurs sur le téléphone (requis, format, unicité)",
     *                     @OA\Items(type="string", example="Ce numéro de téléphone est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.adresse",
     *                     type="array",
     *                     description="Erreur si l'adresse est manquante",
     *                     @OA\Items(type="string", example="Le champ client.adresse est obligatoire.")
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

            // 3. Créer le compte
            $compte = Compte::create([
                'numeroCompte' => Compte::generateNumeroCompte(),
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
}
