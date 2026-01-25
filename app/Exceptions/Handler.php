<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($this->isApiRequest($request)) {
                return $this->forbidden(__('messages.forbidden'));
            }
        });

        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($this->isApiRequest($request)) {
                $message = $e->getMessage() ?: __('messages.forbidden');
                return $this->forbidden($message);
            }
        });
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->is('v1/*') || $request->expectsJson();
    }

    public function render($request, Throwable $e): Response
    {
        if ($this->isApiRequest($request)) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Determine if the exception handler should return JSON.
     * Override parent to prioritize URL path over Accept header.
     */
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        // Always return JSON for API routes
        if ($request->is('api/*') || $request->is('v1/*')) {
            return true;
        }

        return parent::shouldReturnJson($request, $e);
    }

    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Laravel's ValidationException - use localized validation messages
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->validationError($e->errors());
        }

        // Custom ValidationException
        if ($e instanceof \App\Exceptions\ValidationException) {
            $message = $e->getMessage() ?: 'messages.validation_failed';

            return $this->validationError([], $message);
        }

        // ModelNotFoundException - Laravel's Eloquent model not found
        if ($e instanceof ModelNotFoundException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->notFound($messageKey);
        }

        // AuthenticationException - user not authenticated
        if ($e instanceof AuthenticationException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->unauthorized($messageKey);
        }

        // AuthorizationException - user not authorized
        if ($e instanceof AuthorizationException) {
            // Try to get message from exception first, then from response, then fallback to translation key
            $message = $e->getMessage();
            
            // If getMessage() is empty but response has message, use that
            if (empty($message) && method_exists($e, 'response') && $e->response() && $e->response()->message()) {
                $message = $e->response()->message();
            }
            
            // Final fallback to translation key
            if (empty($message)) {
                $message = $this->getExceptionMessageKey($e);
            }

            \Log::critical('AUTHORIZATION_EXCEPTION_DEBUG_v2', [
                'exception_message' => $e->getMessage(),
                'response_message' => method_exists($e, 'response') && $e->response() ? $e->response()->message() : null,
                'final_message' => $message,
                'translated_forbidden' => __('messages.forbidden'),
            ]);

            return $this->forbidden($message);
        }

        // Custom application exceptions
        if ($e instanceof InvalidFilterException) {
            // Use custom message if provided, otherwise use translation key
            $message = $e->getMessage() ?: 'messages.invalid_request';

            return $this->error(
                $message,
                [],
                400,
                ['filter' => $e->getInvalidFilters()]
            );
        }

        if ($e instanceof InvalidSortException) {
            // Use custom message if provided, otherwise use translation key
            $message = $e->getMessage() ?: 'messages.invalid_request';

            return $this->error(
                $message,
                [],
                400,
                ['sort' => [$e->getInvalidSort()]]
            );
        }

        if ($e instanceof InvalidFilterQuery) {
            return $this->error($e->getMessage(), [], 400);
        }

        if ($e instanceof InvalidSortQuery) {
            return $this->error($e->getMessage(), [], 400);
        }

        if ($e instanceof ResourceNotFoundException) {
            // Use custom message if provided, otherwise use translation key
            $message = $e->getMessage() ?: 'messages.not_found';

            return $this->notFound($message);
        }

        if ($e instanceof UnauthorizedException) {
            // Use custom message if provided, otherwise use translation key
            $message = $e->getMessage() ?: 'messages.unauthorized';

            return $this->unauthorized($message);
        }

        if ($e instanceof ForbiddenException) {
            // Use custom message if provided, otherwise use translation key
            $message = $e->getMessage() ?: 'messages.forbidden';

            return $this->forbidden($message);
        }

        if ($e instanceof DuplicateResourceException) {
            // Use custom message if provided, otherwise use translation key
            $message = $e->getMessage() ?: 'messages.duplicate_entry';

            return $this->error($message, [], 409);
        }

        // Generic BusinessException - must come after specific exceptions that extend it
        if ($e instanceof BusinessException) {
            // Use custom message if provided, otherwise use translation key
            $message = $e->getMessage() ?: 'messages.error';

            return $this->error($message, [], 422);
        }

        // Symfony HTTP exceptions
        if ($e instanceof NotFoundHttpException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->notFound($messageKey);
        }

        if ($e instanceof UnauthorizedHttpException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->unauthorized($messageKey);
        }

        if ($e instanceof AccessDeniedHttpException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->forbidden($messageKey);
        }

        // Generic HTTP exceptions
        if ($e instanceof HttpExceptionInterface || $e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $messageKey = $this->getExceptionMessageKey($e);

            if (config('app.debug')) {
                return $this->error($messageKey, [], $statusCode, [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return $this->error($messageKey, [], $statusCode);
        }

        // Fallback for unmapped exceptions
        $messageKey = $this->getExceptionMessageKey($e);

        if (config('app.debug')) {
            return $this->error($messageKey, [], 500, [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $this->error($messageKey, [], 500);
    }

    /**
     * Map exception to translation key
     *
     * @return string Translation key for the exception
     */
    protected function getExceptionMessageKey(Throwable $e): string
    {
        // Map specific exception types to translation keys
        return match (true) {
            $e instanceof ModelNotFoundException => 'messages.not_found',
            $e instanceof AuthenticationException => 'messages.unauthenticated',
            $e instanceof AuthorizationException => 'messages.forbidden',
            $e instanceof NotFoundHttpException => 'messages.not_found',
            $e instanceof UnauthorizedHttpException => 'messages.unauthorized',
            $e instanceof AccessDeniedHttpException => 'messages.forbidden',
            default => 'messages.server_error',
        };
    }
}
