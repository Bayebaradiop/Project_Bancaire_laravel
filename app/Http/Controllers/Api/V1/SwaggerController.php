<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Gestion de Comptes Bancaires",
 *     description="Documentation complète de l'API REST pour la gestion des comptes bancaires. Cette API permet de gérer les comptes clients avec pagination, filtres avancés et recherche.",
 *     @OA\Contact(
 *         email="support@example.com",
 *         name="Support API"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8001/api/v1",
 *     description="Serveur de développement local"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8001/api/v1",
 *     description="Serveur local alternatif"
 * )
 *
 * @OA\Tag(
 *     name="Comptes",
 *     description="Endpoints pour la gestion des comptes bancaires - Création, consultation, modification et suppression"
 * )
 *
 * @OA\Tag(
 *     name="Health",
 *     description="Endpoints de vérification de l'état de l'API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Authentification via Laravel Sanctum. Pour obtenir un token, utilisez l'endpoint /auth/login (actuellement désactivé en développement)"
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Réponse d'erreur",
 *     description="Format standard pour les réponses d'erreur",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Erreur lors du traitement de la requête"),
 *     @OA\Property(property="errors", type="object", description="Détails des erreurs de validation (optionnel)")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     title="Métadonnées de pagination",
 *     description="Informations sur la pagination des résultats",
 *     @OA\Property(property="currentPage", type="integer", example=1, description="Page actuelle"),
 *     @OA\Property(property="totalPages", type="integer", example=5, description="Nombre total de pages"),
 *     @OA\Property(property="totalItems", type="integer", example=50, description="Nombre total d'éléments"),
 *     @OA\Property(property="itemsPerPage", type="integer", example=10, description="Nombre d'éléments par page"),
 *     @OA\Property(property="hasNext", type="boolean", example=true, description="Indique s'il y a une page suivante"),
 *     @OA\Property(property="hasPrevious", type="boolean", example=false, description="Indique s'il y a une page précédente"),
 *     @OA\Property(property="from", type="integer", example=1, description="Index du premier élément de la page"),
 *     @OA\Property(property="to", type="integer", example=10, description="Index du dernier élément de la page")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     type="object",
 *     title="Liens de pagination",
 *     description="Liens de navigation pour la pagination",
 *     @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1", description="Lien vers la page actuelle"),
 *     @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1", description="Lien vers la première page"),
 *     @OA\Property(property="last", type="string", example="/api/v1/comptes?page=5", description="Lien vers la dernière page"),
 *     @OA\Property(property="next", type="string", nullable=true, example="/api/v1/comptes?page=2", description="Lien vers la page suivante"),
 *     @OA\Property(property="previous", type="string", nullable=true, example=null, description="Lien vers la page précédente")
 * )
 */
class SwaggerController extends Controller
{
    // Ce controller sert uniquement pour les annotations Swagger
}
