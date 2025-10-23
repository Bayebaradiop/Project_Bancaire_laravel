<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/health",
     *     operationId="healthCheck",
     *     tags={"Health"},
     *     summary="Vérifier l'état de l'API",
     *     description="Endpoint pour vérifier si l'API fonctionne correctement",
     *     @OA\Response(
     *         response=200,
     *         description="L'API fonctionne correctement",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API is running"),
     *             @OA\Property(property="version", type="string", example="v1"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-10-23T12:29:16+00:00")
     *         )
     *     )
     * )
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API is running',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
