<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackUserRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if ($user) {
            $key = 'user_requests_' . $user->id . '_' . now()->format('Y-m-d');
            
            // Incrémenter le compteur de requêtes
            $requestCount = Cache::increment($key);
            
            // Définir l'expiration à la fin de la journée si c'est la première requête
            if ($requestCount == 1) {
                Cache::put($key, 1, now()->endOfDay());
            }
            
            // Logger si l'utilisateur atteint 10 requêtes
            if ($requestCount == 10) {
                Log::info('Utilisateur a atteint 10 requêtes', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'nom' => $user->nomComplet,
                    'date' => now()->format('Y-m-d'),
                    'heure' => now()->format('H:i:s'),
                    'ip' => $request->ip(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                ]);
            }
            
            // Logger également à chaque requête après 10
            if ($requestCount > 10) {
                Log::warning('Utilisateur dépasse 10 requêtes', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'nom' => $user->nomComplet,
                    'total_requests' => $requestCount,
                    'date' => now()->format('Y-m-d'),
                    'heure' => now()->format('H:i:s'),
                    'ip' => $request->ip(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                ]);
            }
        }
        
        return $next($request);
    }
}
