<?php

declare(strict_types=1);

namespace Modules\Benchmark\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Benchmark\Services\BenchmarkService;

class BenchmarkController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected readonly BenchmarkService $service
    ) {}

    /**
     * Benchmark 1: Baseline (No DB, No Logic).
     */
    public function baseline()
    {
        return response()->json($this->service->baseline());
    }

    /**
     * Benchmark 2: Light Logic (1 DB query, simple calc).
     */
    public function light()
    {
        return response()->json($this->service->light());
    }

    /**
     * Benchmark 3: Heavy Logic (Many rows, sorting/looping).
     */
    public function heavy()
    {
        return response()->json($this->service->heavy());
    }
}
