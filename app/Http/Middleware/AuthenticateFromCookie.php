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
            $accessToken = $request->cookie('access_token');
            
            if ($accessToken) {
                $request->headers->set('Authorization', 'Bearer ' . $accessToken);
                Log::info('Cookie Auth: JWT token found and injected');
            } else {
                Log::warning('Cookie Auth: No access_token cookie found');
            }
        } catch (\Throwable $e) {
            Log::error('Cookie Auth Error', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }
}
