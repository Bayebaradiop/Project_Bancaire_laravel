<?php

namespace App\Services;

use App\Models\User;
use App\Helpers\CookieManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    protected CookieManager $cookieManager;

    public function __construct(CookieManager $cookieManager)
    {
        $this->cookieManager = $cookieManager;
    }

    /**
     * Authentifier l'utilisateur et générer les tokens JWT.
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws \Exception
     */
    public function login(string $email, string $password): array
    {
        // 1. Vérifier les credentials
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Email ou mot de passe incorrect', 401);
        }

        // 2. Générer les tokens JWT
        $accessToken = JWTAuth::fromUser($user);
        
        // Générer un refresh token (simple UUID pour l'exemple)
        $refreshToken = Str::uuid()->toString();

        // 3. Créer les cookies HttpOnly
        $accessCookie = $this->cookieManager->createAccessTokenCookie($accessToken);
        $refreshCookie = $this->cookieManager->createRefreshTokenCookie($refreshToken);

        // 4. Retourner la réponse avec les cookies
        return [
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60, // TTL en secondes
            ],
            'access_cookie' => $accessCookie,
            'refresh_cookie' => $refreshCookie,
        ];
    }

    /**
    /**
     * Renouveler l'access token avec le refresh token.
     *
     * @param string $refreshToken
     * @return array
     * @throws \Exception
     */
    public function refresh(string $refreshToken): array
    {
        try {
            // Rafraîchir le token JWT
            $newToken = JWTAuth::refresh();
            
            // Créer un nouveau cookie d'accès
            $accessCookie = $this->cookieManager->createAccessTokenCookie($newToken);
            
            // Générer un nouveau refresh token
            $newRefreshToken = Str::uuid()->toString();
            $refreshCookie = $this->cookieManager->createRefreshTokenCookie($newRefreshToken);
            
            return [
                'success' => true,
                'message' => 'Token rafraîchi avec succès',
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'Bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
                'cookies' => [
                    'access_token' => $accessCookie,
                    'refresh_token' => $refreshCookie,
                ],
            ];
        } catch (\Exception $e) {
            throw new \Exception('Impossible de rafraîchir le token: ' . $e->getMessage(), 401);
        }
    }

    /**
     * Déconnecter l'utilisateur et révoquer le token JWT.
     *
     * @param User $user
     * @return array
     */
    public function logout(User $user): array
    {
        // Invalider le token JWT
        JWTAuth::invalidate(JWTAuth::getToken());

        // Supprimer les cookies
        $clearAccessCookie = $this->cookieManager->clearAccessTokenCookie();
        $clearRefreshCookie = $this->cookieManager->clearRefreshTokenCookie();

        return [
            'success' => true,
            'message' => 'Déconnexion réussie',
            'cookies' => [
                'access_token' => $clearAccessCookie,
                'refresh_token' => $clearRefreshCookie,
            ],
        ];
    }
}
