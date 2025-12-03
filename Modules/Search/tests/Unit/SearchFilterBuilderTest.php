<?php

namespace Modules\Search\Tests\Unit;

use Modules\Search\Services\SearchFilterBuilder;
use Tests\TestCase;

class SearchFilterBuilderTest extends TestCase
{
    public function test_add_category_filter(): void
    {
        $builder = new SearchFilterBuilder;
        $builder->addCategoryFilter([1, 2, 3]);

        $filters = $builder->build();

        $this->assertArrayHasKey('category_id', $filters);
        $this->assertEquals([1, 2, 3], $filters['category_id']);
    }

    public function test_add_level_filter(): void
    {
        $builder = new SearchFilterBuilder;
        $builder->addLevelFilter(['dasar', 'lanjut']);

        $filters = $builder->build();

        $this->assertArrayHasKey('level_tag', $filters);
        $this->assertEquals(['dasar', 'lanjut'], $filters['level_tag']);
    }

    public function test_add_instructor_filter(): void
    {
        $builder = new SearchFilterBuilder;
        $builder->addInstructorFilter([10, 20]);

        $filters = $builder->build();

        $this->assertArrayHasKey('instructor_id', $filters);
        $this->assertEquals([10, 20], $filters['instructor_id']);
    }

    public function test_add_duration_filter(): void
    {
        $builder = new SearchFilterBuilder;
        $builder->addDurationFilter(30, 120);

        $filters = $builder->build();

        $this->assertArrayHasKey('duration_estimate', $filters);
        $this->assertEquals(['min' => 30, 'max' => 120], $filters['duration_estimate']);
    }

    public function test_add_status_filter(): void
    {
        $builder = new SearchFilterBuilder;
        $builder->addStatusFilter(['published', 'draft']);

        $filters = $builder->build();

        $this->assertArrayHasKey('status', $filters);
        $this->assertEquals(['published', 'draft'], $filters['status']);
    }

    public function test_multiple_filters_can_be_chained(): void
    {
        $builder = new SearchFilterBuilder;
        $filters = $builder
            ->addCategoryFilter([1, 2])
            ->addLevelFilter(['dasar'])
            ->addStatusFilter(['published'])
            ->build();

        $this->assertArrayHasKey('category_id', $filters);
        $this->assertArrayHasKey('level_tag', $filters);
        $this->assertArrayHasKey('status', $filters);
        $this->assertCount(3, $filters);
    }

    public function test_build_returns_empty_array_when_no_filters_added(): void
    {
        $builder = new SearchFilterBuilder;
        $filters = $builder->build();

        $this->assertIsArray($filters);
        $this->assertEmpty($filters);
    }
}
