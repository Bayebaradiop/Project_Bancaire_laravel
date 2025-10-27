<?php

namespace App\Helpers;

use Symfony\Component\HttpFoundation\Cookie;

class CookieManager
{
    /**
     * Crée les cookies d'authentification (access_token et refresh_token)
     *
     * @param string $accessToken Token d'accès JWT
     * @param string $refreshToken Token de rafraîchissement
     * @return array<Cookie> Tableau de cookies
     */
    public function createAuthCookies(string $accessToken, string $refreshToken): array
    {
        return [
            'access_token' => $this->createAccessTokenCookie($accessToken),
            'refresh_token' => $this->createRefreshTokenCookie($refreshToken),
        ];
    }

    /**
     * Crée le cookie pour l'access token
     *
     * @param string $token Token d'accès
     * @return Cookie
     */
    public function createAccessTokenCookie(string $token): Cookie
    {
        return cookie(
            'access_token',
            $token,
            60,                          // 60 minutes
            '/',                         // Path
            null,                        // Domain - null permet localhost
            app()->environment('production'), // Secure (true en production)
            true,                        // HttpOnly - protection XSS
            false,                       // Raw
            'lax'                        // SameSite - permet les requêtes same-site
        );
    }

    /**
     * Crée le cookie pour le refresh token
     *
     * @param string $token Token de rafraîchissement
     * @return Cookie
     */
    public function createRefreshTokenCookie(string $token): Cookie
    {
        return cookie(
            'refresh_token',
            $token,
            43200,                       // 30 jours (en minutes)
            '/',
            null,
            app()->environment('production'), // Secure (true en production)
            true,                        // HttpOnly - protection XSS
            false,
            'lax'
        );
    }

    /**
     * Crée les cookies pour supprimer les tokens (logout)
     *
     * @return array<Cookie> Cookies expirés
     */
    public function clearAuthCookies(): array
    {
        return [
            'access_token' => cookie()->forget('access_token'),
            'refresh_token' => cookie()->forget('refresh_token'),
        ];
    }

    /**
     * Supprime le cookie access_token
     *
     * @return Cookie
     */
    public function clearAccessTokenCookie(): Cookie
    {
        return cookie()->forget('access_token');
    }

    /**
     * Supprime le cookie refresh_token
     *
     * @return Cookie
     */
    public function clearRefreshTokenCookie(): Cookie
    {
        return cookie()->forget('refresh_token');
    }
}
