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
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Bancaire - Gestion des Comptes",
 *     description="API REST pour la gestion des comptes bancaires. Permet de créer, lister et consulter des comptes avec validation complète.",
 *     @OA\Contact(
 *         email="support@banque.sn"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement local"
 * )
 * 
 * @OA\Server(
 *     url="https://project-bancaire-laravel.onrender.com/api",
 *     description="Serveur de production"
 * )
 * 
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
     *     path="/v1/comptes",
     *     summary="Lister tous les comptes",
     *     description="Récupère la liste de tous les comptes avec pagination et filtres",
     *     operationId="getComptes",
     *     tags={"Comptes"},
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
     *         description="Filtrer par type (epargne, cheque)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès"
     *     ),
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
     *     path="/v1/comptes/numero/{numero}",
     *     summary="Obtenir un compte par numéro",
     *     description="Récupère les détails d'un compte par son numéro",
     *     operationId="getCompteByNumero",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès"
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé")
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
     *     tags={"Comptes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "courant", "cheque"}),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "USD", "EUR"}),
     *             @OA\Property(
     *                 property="client",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", nullable=true),
     *                 @OA\Property(property="titulaire", type="string"),
     *                 @OA\Property(property="nci", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="telephone", type="string"),
     *                 @OA\Property(property="adresse", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès"
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
