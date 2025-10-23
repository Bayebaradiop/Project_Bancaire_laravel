<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseFormat
{
    /**
     * Success response with data
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'Opération réussie', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Success response for resource created
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function created($data, string $message = 'Ressource créée avec succès'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Success response for resource updated
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function updated($data, string $message = 'Ressource mise à jour avec succès'): JsonResponse
    {
        return $this->success($data, $message, 200);
    }

    /**
     * Success response for resource deleted
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function deleted(string $message = 'Ressource supprimée avec succès'): JsonResponse
    {
        return $this->success(null, $message, 200);
    }

    /**
     * Success response with pagination
     *
     * @param ResourceCollection|array $data
     * @param object $paginator
     * @param string $baseUrl
     * @param string $message
     * @return JsonResponse
     */
    protected function paginated($data, $paginator, string $baseUrl, string $message = 'Données récupérées avec succès'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => $this->formatPagination($paginator),
            'links' => $this->formatPaginationLinks($paginator, $baseUrl),
        ], 200);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @param mixed $data
     * @return JsonResponse
     */
    protected function error(string $message, int $statusCode = 400, array $errors = null, $data = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationError(array $errors, string $message = 'Erreur de validation'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Non autorisé'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Accès interdit'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Conflict response
     *
     * @param string $message
     * @param mixed $data
     * @return JsonResponse
     */
    protected function conflict(string $message = 'Conflit détecté', $data = null): JsonResponse
    {
        return $this->error($message, 409, null, $data);
    }

    /**
     * Server error response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Erreur serveur'): JsonResponse
    {
        return $this->error($message, 500);
    }

    /**
     * Format pagination metadata
     *
     * @param object $paginator
     * @return array
     */
    protected function formatPagination($paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'totalItems' => $paginator->total(),
            'itemsPerPage' => $paginator->perPage(),
            'hasNext' => $paginator->hasMorePages(),
            'hasPrevious' => $paginator->currentPage() > 1,
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * Format pagination links
     *
     * @param object $paginator
     * @param string $baseUrl
     * @return array
     */
    protected function formatPaginationLinks($paginator, string $baseUrl): array
    {
        $queryParams = request()->query();
        
        return [
            'self' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $paginator->currentPage()])),
            'first' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => 1])),
            'last' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $paginator->lastPage()])),
            'next' => $paginator->hasMorePages() 
                ? $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $paginator->currentPage() + 1])) 
                : null,
            'previous' => $paginator->currentPage() > 1 
                ? $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $paginator->currentPage() - 1])) 
                : null,
        ];
    }

    /**
     * Build URL with query parameters
     *
     * @param string $baseUrl
     * @param array $params
     * @return string
     */
    protected function buildUrl(string $baseUrl, array $params): string
    {
        // Supprimer les paramètres null ou vides
        $params = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });

        return $baseUrl . (count($params) ? '?' . http_build_query($params) : '');
    }

    /**
     * Response for no content
     *
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Response with custom status code
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param bool $success
     * @return JsonResponse
     */
    protected function customResponse($data, string $message, int $statusCode, bool $success = true): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
