<?php

namespace Modules\Search\Contracts;

use Modules\Auth\Models\User;
use Modules\Search\DTOs\SearchResultDTO;

interface SearchServiceInterface
{
    /**
     * Perform full-text search with filters.
     *
     * @param  string  $query  Search query
     * @param  array  $filters  Filter criteria
     * @param  array  $sort  Sorting options
     */
    public function search(string $query, array $filters = [], array $sort = []): SearchResultDTO;

    /**
     * Get autocomplete suggestions.
     *
     * @param  string  $query  Partial query
     * @param  int  $limit  Number of suggestions
     */
    public function getSuggestions(string $query, int $limit = 10): array;

    /**
     * Save search query to history.
     */
    public function saveSearchHistory(User $user, string $query, array $filters = [], int $resultsCount = 0): void;
}
