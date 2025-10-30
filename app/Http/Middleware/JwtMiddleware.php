<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Tenter de récupérer le token depuis le header Authorization
            $token = $request->bearerToken();
            
            // Si pas de token dans le header, vérifier le cookie
            if (!$token) {
                $token = $request->cookie('access_token');
            }

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié. Token JWT requis.'
                ], 401);
            }

            // Définir le token pour JWT
            JWTAuth::setToken($token);
            
            // Authentifier l'utilisateur
            $user = JWTAuth::authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré.'
                ], 401);
            }

            // Ajouter l'utilisateur à la requête
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré.',
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
