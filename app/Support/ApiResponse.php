<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Generic success response
     */
    protected function success(
        mixed $data = null,
        string $message = 'Berhasil',
        int $status = 200,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
                'meta' => $meta,
                'errors' => null,
            ],
            $status
        );
    }

    /**
     * 201 Created
     */
    protected function created(
        mixed $data = null,
        string $message = 'Berhasil dibuat',
        ?array $meta = null
    ): JsonResponse {
        return $this->success($data, $message, 201, $meta);
    }

    /**
     * Generic error response
     */
    protected function error(
        string $message = 'Terjadi kesalahan',
        int $status = 400,
        ?array $errors = null,
        mixed $data = null,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'message' => $message,
                'data' => $data,
                'meta' => $meta,
                'errors' => $errors,
            ],
            $status
        );
    }

    /**
     * Paginated response
     */
    protected function paginateResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Berhasil',
        int $status = 200
    ): JsonResponse {
        return $this->success(
            data: $paginator->items(),
            message: $message,
            status: $status,
            meta: [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_next' => $paginator->hasMorePages(),
                    'has_prev' => $paginator->currentPage() > 1,
                ],
            ]
        );
    }

    /**
     * Validation error (422)
     */
    protected function validationError(
        array $errors,
        string $message = 'Data yang Anda kirim tidak valid. Periksa kembali isian Anda.'
    ): JsonResponse {
        return $this->error(
            message: $message,
            status: 422,
            errors: $errors
        );
    }

    /**
     * Resource not found (404)
     */
    protected function notFound(
        string $message = 'Resource tidak ditemukan'
    ): JsonResponse {
        return $this->error($message, 404);
    }

    /**
     * Unauthorized (401)
     */
    protected function unauthorized(
        string $message = 'Tidak terotorisasi'
    ): JsonResponse {
        return $this->error($message, 401);
    }

    /**
     * Forbidden (403)
     */
    protected function forbidden(
        string $message = 'Akses ditolak'
    ): JsonResponse {
        return $this->error($message, 403);
    }

    /**
     * 204 No Content
     */
    protected function noContent(): JsonResponse
    {
        return response()->json([], 204);
    }
}
