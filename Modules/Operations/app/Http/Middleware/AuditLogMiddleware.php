<?php

namespace Modules\Operations\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Operations\Services\AuditService;

class AuditLogMiddleware
{
    public function __construct(private ?AuditService $auditService = null)
    {
        $this->auditService = $auditService ?? app(AuditService::class);
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log successful state-changing operations
        if ($response->isSuccessful() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->logAudit($request, $response);
        }

        return $response;
    }

    private function logAudit(Request $request, $response): void
    {
        try {
            $route = $request->route();
            if (! $route) {
                return;
            }

            $routeName = $route->getName();
            $auditableRoutes = [
                'enrollments.approve' => 'Approved enrollment',
                'enrollments.decline' => 'Declined enrollment',
                'enrollments.remove' => 'Removed enrollment from course',
                'courses.enrollments.cancel' => 'Cancelled enrollment request',
                'courses.enrollment-key.generate' => 'Generated enrollment key',
                'courses.enrollment-key' => 'Updated enrollment key',
            ];

            if (isset($auditableRoutes[$routeName])) {
                /** @var \Modules\Auth\Models\User|null $user */
                $user = auth('api')->user();

                $this->auditService->log([
                    'user_id' => $user?->id,
                    'action' => $auditableRoutes[$routeName],
                    'model' => $this->extractModelFromRoute($route),
                    'model_id' => $this->extractModelIdFromRoute($route),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'route_name' => $routeName,
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail - don't break the request
            Log::error('Audit logging failed: '.$e->getMessage());
        }
    }

    private function extractModelFromRoute($route): ?string
    {
        $parameters = $route->parameters();

        if (isset($parameters['enrollment'])) {
            return 'Enrollment';
        }
        if (isset($parameters['course'])) {
            return 'Course';
        }

        return null;
    }

    private function extractModelIdFromRoute($route): ?int
    {
        $parameters = $route->parameters();

        if (isset($parameters['enrollment'])) {
            return is_object($parameters['enrollment']) ? $parameters['enrollment']->id : (int) $parameters['enrollment'];
        }
        if (isset($parameters['course'])) {
            return is_object($parameters['course']) ? $parameters['course']->id : (int) $parameters['course'];
        }

        return null;
    }
}
