<?php

namespace Tests\Unit\Foundation;

use App\Support\QueryFilter;
use Tests\TestCase;

class QueryFilterTest extends TestCase
{
    /**
     * Test validateFilters throws exception for invalid filter fields.
     */
    public function test_validate_filters_throws_exception_for_invalid_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid filter fields: invalid_field. Allowed filters: name, email');

        $filter = new QueryFilter([
            'filter' => [
                'name' => 'John',
                'invalid_field' => 'value',
            ],
        ]);

        $filter->allowFilters(['name', 'email']);

        // Use reflection to call validateFilters directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateFilters');
        $method->setAccessible(true);
        $method->invoke($filter);
    }

    /**
     * Test validateFilters accepts valid filter fields.
     */
    public function test_validate_filters_accepts_valid_fields(): void
    {
        $filter = new QueryFilter([
            'filter' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ]);

        $filter->allowFilters(['name', 'email']);

        // Use reflection to call validateFilters directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateFilters');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($filter);
        $this->assertTrue(true);
    }

    /**
     * Test validateFilters allows empty filters.
     */
    public function test_validate_filters_allows_empty_filters(): void
    {
        $filter = new QueryFilter([]);

        $filter->allowFilters(['name', 'email']);

        // Use reflection to call validateFilters directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateFilters');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($filter);
        $this->assertTrue(true);
    }

    /**
     * Test validateFilters allows non-array filters.
     */
    public function test_validate_filters_allows_non_array_filters(): void
    {
        $filter = new QueryFilter([
            'filter' => 'invalid_string',
        ]);

        $filter->allowFilters(['name', 'email']);

        // Use reflection to call validateFilters directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateFilters');
        $method->setAccessible(true);

        // Should not throw exception for non-array filters
        $method->invoke($filter);
        $this->assertTrue(true);
    }

    /**
     * Test validateSorts throws exception for invalid sort field.
     */
    public function test_validate_sorts_throws_exception_for_invalid_field(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field: invalid_field. Allowed sorts: name, created_at');

        $filter = new QueryFilter([
            'sort' => 'invalid_field',
        ]);

        $filter->allowSorts(['name', 'created_at']);

        // Use reflection to call validateSorts directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateSorts');
        $method->setAccessible(true);
        $method->invoke($filter);
    }

    /**
     * Test validateSorts accepts valid sort field.
     */
    public function test_validate_sorts_accepts_valid_field(): void
    {
        $filter = new QueryFilter([
            'sort' => 'name',
        ]);

        $filter->allowSorts(['name', 'created_at']);

        // Use reflection to call validateSorts directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateSorts');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($filter);
        $this->assertTrue(true);
    }

    /**
     * Test validateSorts accepts descending sort with minus prefix.
     */
    public function test_validate_sorts_accepts_descending_sort(): void
    {
        $filter = new QueryFilter([
            'sort' => '-created_at',
        ]);

        $filter->allowSorts(['name', 'created_at']);

        // Use reflection to call validateSorts directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateSorts');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($filter);
        $this->assertTrue(true);
    }

    /**
     * Test validateSorts allows empty sort.
     */
    public function test_validate_sorts_allows_empty_sort(): void
    {
        $filter = new QueryFilter([]);

        $filter->allowSorts(['name', 'created_at']);

        // Use reflection to call validateSorts directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateSorts');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($filter);
        $this->assertTrue(true);
    }

    /**
     * Test validateSortDirection returns valid direction.
     */
    public function test_validate_sort_direction_returns_valid_direction(): void
    {
        $filter = new QueryFilter([
            'sort' => '-name',
        ]);

        $filter->allowSorts(['name']);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateSortDirection');
        $method->setAccessible(true);

        $direction = $method->invoke($filter);

        $this->assertEquals('desc', $direction);
    }

    /**
     * Test validateSortDirection returns asc for ascending sort.
     */
    public function test_validate_sort_direction_returns_asc_for_ascending(): void
    {
        $filter = new QueryFilter([
            'sort' => 'name',
        ]);

        $filter->allowSorts(['name']);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateSortDirection');
        $method->setAccessible(true);

        $direction = $method->invoke($filter);

        $this->assertEquals('asc', $direction);
    }

    /**
     * Test validation skips when no allowed filters defined.
     */
    public function test_validation_skips_when_no_allowed_filters_defined(): void
    {
        $filter = new QueryFilter([
            'filter' => [
                'any_field' => 'value',
            ],
        ]);

        // Don't set allowed filters

        // Use reflection to call validateFilters directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateFilters');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($filter);
        $this->assertTrue(true);
    }

    /**
     * Test validation skips when no allowed sorts defined.
     */
    public function test_validation_skips_when_no_allowed_sorts_defined(): void
    {
        $filter = new QueryFilter([
            'sort' => 'any_field',
        ]);

        // Don't set allowed sorts

        // Use reflection to call validateSorts directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateSorts');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($filter);
        $this->assertTrue(true);
    }

    /**
     * Test multiple invalid filters are listed in error message.
     */
    public function test_multiple_invalid_filters_listed_in_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid filter fields:.*invalid1.*invalid2/');

        $filter = new QueryFilter([
            'filter' => [
                'name' => 'John',
                'invalid1' => 'value1',
                'invalid2' => 'value2',
            ],
        ]);

        $filter->allowFilters(['name', 'email']);

        // Use reflection to call validateFilters directly
        $reflection = new \ReflectionClass($filter);
        $method = $reflection->getMethod('validateFilters');
        $method->setAccessible(true);
        $method->invoke($filter);
    }
}
