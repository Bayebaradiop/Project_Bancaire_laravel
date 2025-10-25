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

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Endpoints pour gérer les comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseFormat, Cacheable;

    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Lister tous les comptes",
     *     description="Récupère la liste de tous les comptes avec pagination et filtres",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "derniereModification", "numeroCompte"}, default="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des comptes récupérée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="numeroCompte", type="string"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="solde", type="number"),
     *                     @OA\Property(property="devise", type="string"),
     *                     @OA\Property(property="statut", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="pagination", type="object"),
     *             @OA\Property(property="links", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=422, description="Erreur de validation")
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
     *     path="/comptes/numero/{numero}",
     *     summary="Obtenir un compte par numéro",
     *     description="Récupère les détails d'un compte par son numéro",
     *     operationId="getCompteByNumero",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte",
     *         required=true,
     *         @OA\Schema(type="string")
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
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="numeroCompte", type="string"),
     *                 @OA\Property(property="titulaire", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="solde", type="number"),
     *                 @OA\Property(property="devise", type="string"),
     *                 @OA\Property(property="statut", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function showByNumero(string $numero): JsonResponse
    {
        // Utiliser le cache pour 10 minutes
        $compte = $this->remember("compte:numero:{$numero}", function () use ($numero) {
            return Compte::with(['client.user', 'transactions'])->numero($numero)->first();
        }, 600);

        if (!$compte) {
            throw new CompteNotFoundException('Compte non trouvé');
        }

        return $this->success(
            new CompteResource($compte),
            'Compte récupéré avec succès'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     summary="Créer un nouveau compte bancaire",
     *     description="Créer un nouveau compte pour un client existant (avec client.id) ou un nouveau client (avec titulaire, nci, email, telephone, adresse)",
     *     tags={"Comptes"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Pour un nouveau client, omettez 'client.id' et fournissez tous les autres champs. Pour un client existant, fournissez uniquement 'client.id'",
     *         @OA\JsonContent(
     *             required={"type", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "courant", "cheque"}, example="epargne", description="Type de compte"),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "USD", "EUR"}, example="FCFA", description="Devise du compte"),
     *             @OA\Property(
     *                 property="client",
     *                 type="object",
     *                 description="Informations du client - Fournir soit 'id' pour client existant, soit tous les autres champs pour nouveau client",
     *                 @OA\Property(property="id", type="string", format="uuid", example=null, nullable=true, description="UUID du client existant (optionnel - si fourni, les autres champs client sont ignorés)"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Sall", description="Nom complet du titulaire (requis si client.id absent)"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123", description="Numéro NCI sénégalais valide - 13 chiffres commençant par 1 ou 2 (requis si client.id absent)"),
     *                 @OA\Property(property="email", type="string", format="email", example="amadou.sall@example.com", description="Email unique du client (requis si client.id absent)"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567", description="Téléphone sénégalais valide au format +221 suivi de 70/75/76/77/78 (requis si client.id absent)"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Plateau", description="Adresse complète du client (requis si client.id absent)")
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
     *         description="Erreur de validation"
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
