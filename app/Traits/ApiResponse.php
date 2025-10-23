<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $statusCode = 200): JsonResponse
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
     * Success response with pagination
     *
     * @param mixed $data
     * @param array $pagination
     * @param array $links
     * @param string $message
     * @return JsonResponse
     */
    protected function successResponseWithPagination($data, array $pagination, array $links, string $message = 'Opération réussie'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination,
            'links' => $links,
        ], 200);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
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
    protected function validationErrorResponse(array $errors, string $message = 'Erreur de validation'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Non autorisé'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Accès interdit'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Build pagination metadata
     *
     * @param object $paginator
     * @return array
     */
    protected function buildPaginationMetadata($paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'totalItems' => $paginator->total(),
            'itemsPerPage' => $paginator->perPage(),
            'hasNext' => $paginator->hasMorePages(),
            'hasPrevious' => $paginator->currentPage() > 1,
        ];
    }

    /**
     * Build pagination links
     *
     * @param object $paginator
     * @param string $baseUrl
     * @return array
     */
    protected function buildPaginationLinks($paginator, string $baseUrl): array
    {
        $queryParams = request()->query();
        
        return [
            'self' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $paginator->currentPage()])),
            'first' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => 1])),
            'last' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $paginator->lastPage()])),
            'next' => $paginator->hasMorePages() ? $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $paginator->currentPage() + 1])) : null,
            'previous' => $paginator->currentPage() > 1 ? $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $paginator->currentPage() - 1])) : null,
        ];
    }

    /**
     * Build URL with query parameters
     *
     * @param string $baseUrl
     * @param array $params
     * @return string
     */
    private function buildUrl(string $baseUrl, array $params): string
    {
        return $baseUrl . '?' . http_build_query($params);
    }
}
