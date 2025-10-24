<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Traits\ApiResponseFormat;
use App\Exceptions\CompteNotFoundException;
use App\Exceptions\CompteBloquedException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\RateLimitExceededException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    use ApiResponseFormat;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Gérer les exceptions personnalisées
        $this->renderable(function (CompteNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error($e->getMessage(), $e->getCode());
            }
        });

        $this->renderable(function (CompteBloquedException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error($e->getMessage(), $e->getCode());
            }
        });

        $this->renderable(function (InsufficientBalanceException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error($e->getMessage(), $e->getCode());
            }
        });

        $this->renderable(function (RateLimitExceededException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error($e->getMessage(), $e->getCode());
            }
        });

        // Gérer les erreurs de validation
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error(
                    'Les données fournies sont invalides',
                    422,
                    $e->errors()
                );
            }
        });

        // Gérer les modèles non trouvés
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error('Ressource non trouvée', 404);
            }
        });

        // Gérer les routes non trouvées
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error('Route non trouvée', 404);
            }
        });
    }
}
