<?php

namespace App\Services;

use App\Models\User;
use App\Helpers\CookieManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use Laravel\Passport\RefreshToken;

class AuthService
{
    protected CookieManager $cookieManager;

    public function __construct(CookieManager $cookieManager)
    {
        $this->cookieManager = $cookieManager;
    }

    /**
     * Authentifier l'utilisateur et générer les tokens.
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

        // 2. Générer les tokens via Passport
        $tokenResult = $user->createToken('auth_token');
        $accessToken = $tokenResult->accessToken;
        $refreshToken = $tokenResult->token->refresh_token;

        // 3. Créer les cookies HttpOnly
        $accessCookie = $this->cookieManager->createAccessTokenCookie($accessToken);
        $refreshCookie = $this->cookieManager->createRefreshTokenCookie($refreshToken);

        // 4. Retourner la réponse avec les cookies
        return [
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 heure
            ],
            'cookies' => [
                'access_token' => $accessCookie,
                'refresh_token' => $refreshCookie,
            ],
        ];
    }

    /**
     * Renouveler l'access token avec le refresh token.
     *
     * @param string $refreshToken
     * @return array
     * @throws \Exception
     */
    public function refresh(string $refreshToken): array
    {
        // 1. Vérifier que le refresh token existe et est valide
        $refreshTokenModel = RefreshToken::where('id', $refreshToken)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$refreshTokenModel) {
            throw new \Exception('Refresh token invalide ou expiré', 401);
        }

        // 2. Récupérer l'access token associé
        $accessTokenModel = Token::find($refreshTokenModel->access_token_id);

        if (!$accessTokenModel) {
            throw new \Exception('Access token introuvable', 401);
        }

        // 3. Révoquer l'ancien access token
        $accessTokenModel->revoke();

        // 4. Récupérer l'utilisateur
        $user = User::find($accessTokenModel->user_id);

        if (!$user) {
            throw new \Exception('Utilisateur introuvable', 401);
        }

        // 5. Générer un nouveau access token
        $tokenResult = $user->createToken('auth_token_refreshed');
        $newAccessToken = $tokenResult->accessToken;

        // 6. Créer le nouveau cookie HttpOnly
        $accessCookie = $this->cookieManager->createAccessTokenCookie($newAccessToken);

        // 7. Retourner la réponse avec le nouveau cookie
        return [
            'success' => true,
            'message' => 'Token renouvelé avec succès',
            'data' => [
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 heure
            ],
            'cookies' => [
                'access_token' => $accessCookie,
            ],
        ];
    }

    /**
     * Déconnecter l'utilisateur et révoquer les tokens.
     *
     * @param User $user
     * @return array
     */
    public function logout(User $user): array
    {
        // 1. Révoquer tous les tokens de l'utilisateur
        $user->tokens()->each(function ($token) {
            $token->revoke();
        });

        // 2. Supprimer les cookies
        $clearAccessCookie = $this->cookieManager->clearAccessTokenCookie();
        $clearRefreshCookie = $this->cookieManager->clearRefreshTokenCookie();

        // 3. Retourner la réponse
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
