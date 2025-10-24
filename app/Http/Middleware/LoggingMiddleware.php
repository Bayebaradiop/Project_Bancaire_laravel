<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoggingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Logger uniquement les opérations de création (POST)
        if ($request->isMethod('post')) {
            Log::info('Opération de création', [
                'date_heure' => now()->toDateTimeString(),
                'host' => $request->getHost(),
                'ip' => $request->ip(),
                'nom_operation' => 'Création',
                'ressource' => $request->path(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'status_code' => $response->status(),
            ]);
        }

        return $response;
    }
}
