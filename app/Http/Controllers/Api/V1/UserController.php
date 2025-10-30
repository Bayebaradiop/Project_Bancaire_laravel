<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Clients",
 *     description="Gestion des clients"
 * )
 */
class UserController extends Controller
{
    /**
     * Récupérer un client à partir de son numéro de téléphone
     *
     * @OA\Get(
     *     path="/v1/clients/telephone/{telephone}",
     *     tags={"Clients"},
     *     summary="Récupérer un client par numéro de téléphone",
     *     description="Permet de récupérer les informations d'un client à partir de son numéro de téléphone",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="path",
     *         description="Numéro de téléphone du client (format: +221XXXXXXXXX)",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="+221771234567"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client trouvé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nomComplet", type="string", example="Jean Dupont"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123456"),
     *                 @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
     *                 @OA\Property(property="role", type="string", example="client"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-15T10:30:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-15T10:30:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun client trouvé avec ce numéro de téléphone")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getByPhone(string $telephone): JsonResponse
    {
        // Normaliser le format du numéro de téléphone
        $telephone = trim($telephone);
        
        // Rechercher le client par numéro de téléphone
        $client = User::where('telephone', $telephone)
            ->where('role', 'client')
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun client trouvé avec ce numéro de téléphone'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Client trouvé avec succès',
            'data' => [
                'id' => $client->id,
                'nomComplet' => $client->nomComplet,
                'nci' => $client->nci,
                'email' => $client->email,
                'telephone' => $client->telephone,
                'adresse' => $client->adresse,
                'role' => $client->role,
                'statut' => $client->statut,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ]
        ], 200);
    }
}
