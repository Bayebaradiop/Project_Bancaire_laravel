<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Traits\ApiResponseFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponseFormat;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur (Admin ou Client) et retourne un token JWT dans un cookie HttpOnly sécurisé.

**Comptes de test (créés par le seeder) :**

**Admin :**
- Email : `admin@banque.sn`
- Password : `Admin@2025`
- Accès : Tous les comptes, toutes les opérations

**Client :**
- Email : `client@banque.sn`
- Password : `Client@2025`
- Accès : Uniquement ses propres comptes

**Instructions après connexion :**
1. Copier le `access_token` de la réponse
2. Cliquer sur **Authorize** en haut à droite
3. Coller le token dans **bearerAuth (http, Bearer)**
4. Cliquer sur **Authorize** puis **Close**
5. Tester les endpoints protégés",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
         *         description="Identifiants de connexion",
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@banque.sn", description="Email de l'utilisateur"),
     *             @OA\Property(property="password", type="string", format="password", example="Admin@2025", description="Mot de passe de l'utilisateur")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="refresh_token", type="string", example="a1b2c3d4-e5f6-7g8h-9i0j-k1l2m3n4o5p6"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600, description="Durée de validité du token en secondes")
     *             )
     *         ),
     *         @OA\Header(
     *             header="Set-Cookie",
     *             description="Cookies HttpOnly contenant access_token et refresh_token",
     *             @OA\Schema(type="string", example="access_token=eyJ0eXAiOiJKV1QiLCJhbGc...; refresh_token=a1b2c3d4...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email ou mot de passe incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="L'email est requis")),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string", example="Le mot de passe est requis"))
     *             )
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->input('email'),
                $request->input('password')
            );

                        return response()->json([
                                'success' => $result['success'],
                                'message' => $result['message'],
                                'data' => $result['data'],
                        ])->withCookie($result['access_cookie'])
                            ->withCookie($result['refresh_cookie']);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/refresh",
     *     summary="Rafraîchir le token JWT",
     *     description="Génère un nouveau token d'accès en utilisant le refresh token stocké dans les cookies.",
     *     operationId="refresh",
     *     tags={"Authentification"},
     *     security={{"cookieAuth": {}}, {"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Refresh token manquant ou invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Refresh token manquant")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->cookie('refresh_token');

            if (!$refreshToken) {
                return $this->error('Refresh token manquant', 401);
            }

            $result = $this->authService->refresh($refreshToken);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'],
            ])->withCookie($result['cookies']['access_token'])
              ->withCookie($result['cookies']['refresh_token']);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/logout",
     *     summary="Déconnexion utilisateur",
     *     description="Déconnecte l'utilisateur authentifié et invalide le token JWT. Les cookies sont supprimés.",
     *     operationId="logout",
     *     tags={"Authentification"},
     *     security={{"cookieAuth": {}}, {"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->error('Non authentifié', 401);
            }

            $result = $this->authService->logout($user);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ])->withCookie($result['cookies']['access_token'])
              ->withCookie($result['cookies']['refresh_token']);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
