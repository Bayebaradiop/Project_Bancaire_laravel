<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            // Logger l'utilisateur qui a atteint la limite
            $this->logRateLimitExceeded($request, $key);

            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez réessayer plus tard.',
                'retry_after' => $this->getRetryAfter($key, $decayMinutes),
            ], 429);
        }

        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sprintf(
                'rate_limit:%s:%s',
                sha1($user->id),
                $request->ip()
            );
        }

        return sprintf(
            'rate_limit:%s',
            sha1($request->ip())
        );
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        $attempts = Cache::get($key, 0);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get the number of seconds until the next retry.
     *
     * @param  string  $key
     * @param  int  $decayMinutes
     * @return int
     */
    protected function getRetryAfter(string $key, int $decayMinutes): int
    {
        return Cache::get($key . ':timer', now()->addMinutes($decayMinutes)->timestamp) - now()->timestamp;
    }

    /**
     * Add the limit header information to the given response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    /**
     * Log when a user exceeds the rate limit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return void
     */
    protected function logRateLimitExceeded(Request $request, string $key): void
    {
        $user = $request->user();
        
        Log::warning('Rate limit exceeded', [
            'key' => $key,
            'ip' => $request->ip(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Optionnel : Enregistrer dans une table dédiée pour le suivi
        // RateLimitLog::create([...]);
    }
}
