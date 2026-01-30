<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Services\BenchmarkService;

class BenchmarkController extends Controller
{
    public function __construct(
        private readonly BenchmarkService $service
    ) {}

    /**
     * READ Operation: Get 1000 users.
     */
    public function index(): JsonResponse
    {
        $users = $this->service->getBenchmarkUsers();

        return response()->json([
            'data' => $users,
            'count' => $users->count(),
        ]);
    }

    /**
     * CREATE Operation: Insert 1000 users.
     */
    public function store(): JsonResponse
    {
        $this->service->createBenchmarkUsers();

        return response()->json([
            'message' => 'Successfully created 1000 users',
            'success' => true,
        ], 201);
    }
}
