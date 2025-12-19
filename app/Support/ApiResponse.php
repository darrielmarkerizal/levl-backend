<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Translate a message key or return the string as-is for backward compatibility
     *
     * @param  string  $message  Translation key or plain string
     * @param  array  $params  Parameters for translation substitution
     */
    private function translateMessage(string $message, array $params = []): string
    {
        // Check if the message looks like a translation key (contains a dot)
        // and if the translation exists
        if (str_contains($message, '.') && trans()->has($message)) {
            return __($message, $params);
        }

        // For backward compatibility, return the message as-is if it's not a translation key
        // or if the translation doesn't exist
        return $message;
    }

    protected function success(
        mixed $data = null,
        string $message = 'messages.success',
        array $params = [],
        int $status = 200,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => true,
                'message' => $this->translateMessage($message, $params),
                'data' => $data,
                'meta' => $meta,
                'errors' => null,
            ],
            $status
        );
    }

    protected function created(
        mixed $data = null,
        string $message = 'messages.created',
        array $params = [],
        ?array $meta = null
    ): JsonResponse {
        return $this->success($data, $message, $params, 201, $meta);
    }

    protected function error(
        string $message = 'messages.error',
        array $params = [],
        int $status = 400,
        ?array $errors = null,
        mixed $data = null,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'message' => $this->translateMessage($message, $params),
                'data' => $data,
                'meta' => $meta,
                'errors' => $errors,
            ],
            $status
        );
    }

    protected function paginateResponse(
        LengthAwarePaginator $paginator,
        string $message = 'messages.success',
        int $status = 200,
        ?array $additionalMeta = null,
        array $params = []
    ): JsonResponse {
        $request = request();

        $meta = [
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
        ];

        // Add sorting info if present
        if ($request->has('sort')) {
            $meta['sorting'] = [
                'sort_by' => $request->get('sort'),
                'sort_order' => $request->get('sort_order', 'asc'),
            ];
        }

        // Add filtering info if present
        $filterKeys = ['filter', 'filters'];
        foreach ($filterKeys as $key) {
            if ($request->has($key)) {
                $meta['filtering'] = $request->get($key);
                break;
            }
        }

        // Add search info if present
        if ($request->has('search') && $request->search) {
            $meta['search'] = [
                'query' => $request->get('search'),
            ];
        }

        // Merge additional meta if provided
        if ($additionalMeta) {
            $meta = array_merge($meta, $additionalMeta);
        }

        return $this->success(
            data: $paginator->items(),
            message: $message,
            params: $params,
            status: $status,
            meta: $meta
        );
    }

    protected function validationError(
        array $errors,
        string $message = 'messages.validation_failed',
        array $params = []
    ): JsonResponse {
        return $this->error(
            message: $message,
            params: $params,
            status: 422,
            errors: $errors
        );
    }

    protected function notFound(
        string $message = 'messages.not_found',
        array $params = []
    ): JsonResponse {
        return $this->error($message, $params, 404);
    }

    protected function unauthorized(
        string $message = 'messages.unauthorized',
        array $params = []
    ): JsonResponse {
        return $this->error($message, $params, 401);
    }

    protected function forbidden(
        string $message = 'messages.forbidden',
        array $params = []
    ): JsonResponse {
        return $this->error($message, $params, 403);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json([], 204);
    }

    /**
     * Static helper to translate a message key or return the string as-is
     *
     * @param  string  $message  Translation key or plain string
     * @param  array  $params  Parameters for translation substitution
     */
    private static function translateMessageStatic(string $message, array $params = []): string
    {
        // Check if the message looks like a translation key (contains a dot)
        // and if the translation exists
        if (str_contains($message, '.') && trans()->has($message)) {
            return __($message, $params);
        }

        // For backward compatibility, return the message as-is if it's not a translation key
        // or if the translation doesn't exist
        return $message;
    }

    public static function successStatic(
        mixed $data = null,
        string $message = 'messages.success',
        array $params = [],
        int $status = 200,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => true,
                'message' => self::translateMessageStatic($message, $params),
                'data' => $data,
                'meta' => $meta,
                'errors' => null,
            ],
            $status
        );
    }

    public static function errorStatic(
        string $message = 'messages.error',
        array $params = [],
        int $status = 400,
        ?array $errors = null,
        mixed $data = null,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'message' => self::translateMessageStatic($message, $params),
                'data' => $data,
                'meta' => $meta,
                'errors' => $errors,
            ],
            $status
        );
    }
}
