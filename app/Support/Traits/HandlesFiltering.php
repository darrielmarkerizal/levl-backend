<?php

namespace App\Support\Traits;

use Illuminate\Http\Request;

/**
 * Trait HandlesFiltering
 *
 * Provides standardized filter parameter extraction for API controllers.
 * This trait consolidates repetitive filter extraction logic from multiple controllers
 * into a single, reusable method.
 *
 * Usage:
 * ```php
 * class MyController extends Controller
 * {
 *     use HandlesFiltering;
 *
 *     public function index(Request $request)
 *     {
 *         $params = $this->extractFilterParams($request);
 *         $results = $this->repository->paginate($params);
 *         return $this->paginateResponse($results);
 *     }
 * }
 * ```
 */
trait HandlesFiltering
{
    /**
     * Extract filter parameters from request.
     *
     * Extracts and normalizes filter, sort, pagination, and search parameters
     * from the request. Returns an array with only non-null values.
     *
     * Supported parameters:
     * - filter: Array of filter conditions (e.g., filter[course_id]=1)
     * - sort: Sort field with optional direction prefix (e.g., -created_at for desc)
     * - page: Current page number (default: 1)
     * - per_page: Items per page (default: 15)
     * - search: Search query string
     *
     * @param  Request  $request  The HTTP request containing filter parameters
     * @return array Normalized filter parameters with null values removed
     *
     * @example
     * ```php
     * // Request: ?filter[status]=published&sort=-created_at&page=2&per_page=20&search=keyword
     * $params = $this->extractFilterParams($request);
     * // Returns:
     * // [
     * //     'filter' => ['status' => 'published'],
     * //     'sort' => '-created_at',
     * //     'page' => 2,
     * //     'per_page' => 20,
     * //     'search' => 'keyword'
     * // ]
     * ```
     */
    protected function extractFilterParams(Request $request): array
    {
        $params = [
            'filter' => $request->input('filter', []),
            'sort' => $request->input('sort'),
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 15),
            'search' => $request->input('search'),
        ];

        // Remove null values to avoid passing unnecessary parameters
        return array_filter($params, fn ($value) => ! is_null($value));
    }
}
