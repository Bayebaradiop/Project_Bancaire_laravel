<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Http\Requests\ListCompteRequest;
use App\Models\Compte;
use App\Traits\ApiResponseFormat;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Endpoints pour gérer les comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseFormat;

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
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
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
        $comptes = $query->paginate($limit);

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
     *     path="/comptes/{id}",
     *     summary="Obtenir un compte par ID",
     *     description="Récupère les détails d'un compte spécifique",
     *     operationId="getCompteById",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte récupéré avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $compte = Compte::with(['client.user'])->find($id);

        if (!$compte) {
            return $this->notFound('Compte non trouvé');
        }

        return $this->success(
            new CompteResource($compte),
            'Compte récupéré avec succès'
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
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function showByNumero(string $numero): JsonResponse
    {
        $compte = Compte::with(['client.user'])->numero($numero)->first();

        if (!$compte) {
            return $this->notFound('Compte non trouvé');
        }

        return $this->success(
            new CompteResource($compte),
            'Compte récupéré avec succès'
        );
    }
}
