<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.unauthenticated'),
            ], 401);
        }

        // Superadmin bypasses all role checks
        if ($user->hasRole('Superadmin')) {
            return $next($request);
        }

        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.forbidden'),
            ], 403);
        }

        return $next($request);
    }
}


