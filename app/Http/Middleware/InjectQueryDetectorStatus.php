<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InjectQueryDetectorStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only inject for JSON responses
        if ($response instanceof JsonResponse && config('querydetector.enabled')) {
            $data = $response->getData(true);
            
            // If query_detector not already set (no N+1 detected)
            if (!isset($data['query_detector'])) {
                $data['query_detector'] = [
                    'status' => 'clean',
                    'message' => 'No N+1 queries detected',
                    'queries_count' => 0
                ];
                
                $response->setData($data);
            }
        }

        return $response;
    }
}
