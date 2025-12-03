<?php

namespace Modules\Search\DTOs;

use Illuminate\Pagination\LengthAwarePaginator;

class SearchResultDTO
{
    public function __construct(
        public LengthAwarePaginator $items,
        public string $query,
        public array $filters,
        public array $sort,
        public int $total,
        public float $executionTime
    ) {}

    public function toArray(): array
    {
        return [
            'items' => $this->items->items(),
            'query' => $this->query,
            'filters' => $this->filters,
            'sort' => $this->sort,
            'total' => $this->total,
            'execution_time' => $this->executionTime,
            'pagination' => [
                'current_page' => $this->items->currentPage(),
                'per_page' => $this->items->perPage(),
                'total' => $this->items->total(),
                'last_page' => $this->items->lastPage(),
            ],
        ];
    }
}
