<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthenticateFromCookie
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Si Authorization header existe déjà, le laisser tel quel
            if ($request->header('Authorization')) {
                Log::info('Cookie Auth: Authorization header already present, skipping cookie check');
                return $next($request);
            }

            // Sinon, vérifier le cookie
            $accessToken = $request->cookie('access_token');
            
            if ($accessToken) {
                $request->headers->set('Authorization', 'Bearer ' . $accessToken);
                Log::info('Cookie Auth: JWT token found in cookie and injected');
            } else {
                Log::warning('Cookie Auth: No access_token cookie or Authorization header found');
            }
        } catch (\Throwable $e) {
            Log::error('Cookie Auth Error', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }
}
