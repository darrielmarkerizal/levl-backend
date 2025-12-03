<?php

namespace Modules\Search\Services;

class SearchFilterBuilder
{
    protected array $filters = [];

    /**
     * Add category filter.
     */
    public function addCategoryFilter(array $categoryIds): self
    {
        $this->filters['category_id'] = $categoryIds;

        return $this;
    }

    /**
     * Add level filter.
     */
    public function addLevelFilter(array $levels): self
    {
        $this->filters['level_tag'] = $levels;

        return $this;
    }

    /**
     * Add instructor filter.
     */
    public function addInstructorFilter(array $instructorIds): self
    {
        $this->filters['instructor_id'] = $instructorIds;

        return $this;
    }

    /**
     * Add duration filter.
     */
    public function addDurationFilter(int $minDuration, int $maxDuration): self
    {
        $this->filters['duration_estimate'] = [
            'min' => $minDuration,
            'max' => $maxDuration,
        ];

        return $this;
    }

    /**
     * Add status filter.
     */
    public function addStatusFilter(array $statuses): self
    {
        $this->filters['status'] = $statuses;

        return $this;
    }

    /**
     * Build and return the filters array.
     */
    public function build(): array
    {
        return $this->filters;
    }
}
