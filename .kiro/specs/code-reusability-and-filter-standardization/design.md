# Design Document: Code Reusability and Filter/Sort Standardization

## Overview

Dokumen ini menjelaskan desain teknis untuk mengidentifikasi dan mengeliminasi kode repetitive di project LMS, serta standardisasi mekanisme filtering dan sorting pada API endpoints. Project ini sudah memiliki infrastruktur dasar (`QueryFilter` class dan `FilterableRepository` trait), namun implementasinya tidak konsisten - beberapa repository menggunakan Spatie QueryBuilder, beberapa menggunakan custom QueryFilter, dan beberapa tidak menggunakan filtering sama sekali.

## Architecture

### Current State Analysis

```
┌─────────────────────────────────────────────────────────────────┐
│                    CURRENT STATE ISSUES                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ❌ Mixed filtering approaches:                                 │
│     - Some use Spatie QueryBuilder (CategoryRepository)         │
│     - Some use custom QueryFilter (BaseRepository)              │
│     - Some use manual filtering (SearchController)              │
│                                                                  │
│  ❌ No centralized filter/sort configuration                    │
│  ❌ Repetitive filter building in controllers                   │
│  ❌ Inconsistent parameter naming (sort vs sort_by)             │
│  ❌ No validation for invalid filter/sort fields                │
│  ❌ No documentation of allowed filters per endpoint            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Target State Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      TARGET ARCHITECTURE                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Request ──► Controller ──► Repository ──► QueryFilter          │
│                    │              │                              │
│                    │              ▼                              │
│                    │      FilterConfig                           │
│                    │      (whitelist)                            │
│                    │              │                              │
│                    ▼              ▼                              │
│              Extract params   Validate                           │
│              from request     & Apply                            │
│                                                                  │
│  All repositories declare:                                       │
│  - protected array $allowedFilters                              │
│  - protected array $allowedSorts                                │
│  - protected string $defaultSort                                │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```


## Components and Interfaces

### 1. Standardized Filter Configuration

Each repository should declare its allowed filters and sorts as properties:

```php
// Example: Modules/Content/app/Repositories/AnnouncementRepository.php
class AnnouncementRepository extends BaseRepository
{
    protected array $allowedFilters = [
        'course_id',      // exact match
        'priority',       // exact match
        'status',         // exact match
        'created_at',     // date range
    ];

    protected array $allowedSorts = [
        'id',
        'created_at',
        'published_at',
        'priority',
    ];

    protected string $defaultSort = '-created_at';

    // Use FilterableRepository trait methods
    public function paginate(array $params, int $perPage = 15)
    {
        return $this->filteredPaginate(
            $this->query(),
            $params,
            $this->allowedFilters,
            $this->allowedSorts,
            $this->defaultSort,
            $perPage
        );
    }
}
```

### 2. Controller Filter Parameter Extraction

Create a reusable trait for extracting filter parameters from requests:

```php
// app/Support/Traits/HandlesFiltering.php
namespace App\Support\Traits;

use Illuminate\Http\Request;

trait HandlesFiltering
{
    protected function extractFilterParams(Request $request): array
    {
        $params = [
            'filter' => $request->input('filter', []),
            'sort' => $request->input('sort'),
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 15),
            'search' => $request->input('search'),
        ];

        return array_filter($params, fn($value) => !is_null($value));
    }
}
```


### 3. Enhanced QueryFilter with Validation

Enhance the existing QueryFilter to validate and reject invalid filters:

```php
// app/Support/QueryFilter.php (enhanced)
public function apply(Builder $query)
{
    $this->validateFilters();
    $this->validateSorts();
    
    $this->applyFilters($query);
    $this->applySorting($query);

    return $this->paginate($query);
}

private function validateFilters(): void
{
    $filters = $this->params['filter'] ?? [];
    
    if (!is_array($filters)) {
        return;
    }
    
    $invalidFilters = array_diff(array_keys($filters), $this->allowedFilters);
    
    if (!empty($invalidFilters)) {
        throw new \InvalidArgumentException(
            'Invalid filter fields: ' . implode(', ', $invalidFilters) . 
            '. Allowed filters: ' . implode(', ', $this->allowedFilters)
        );
    }
}

private function validateSorts(): void
{
    $sort = $this->params['sort'] ?? null;
    
    if (empty($sort)) {
        return;
    }
    
    $field = ltrim($sort, '-');
    
    if (!in_array($field, $this->allowedSorts, true)) {
        throw new \InvalidArgumentException(
            "Invalid sort field: {$field}. Allowed sorts: " . 
            implode(', ', $this->allowedSorts)
        );
    }
}
```

### 4. Filter Documentation Generator

Create a command to generate filter/sort documentation:

```php
// app/Console/Commands/GenerateFilterDocumentation.php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateFilterDocumentation extends Command
{
    protected $signature = 'docs:filters';
    protected $description = 'Generate documentation for all API filters and sorts';

    public function handle()
    {
        $repositories = $this->findAllRepositories();
        $documentation = [];

        foreach ($repositories as $repository) {
            $reflection = new \ReflectionClass($repository);
            
            if ($reflection->hasProperty('allowedFilters')) {
                $filters = $reflection->getProperty('allowedFilters')->getValue(new $repository);
                $sorts = $reflection->getProperty('allowedSorts')->getValue(new $repository);
                
                $documentation[$repository] = [
                    'filters' => $filters,
                    'sorts' => $sorts,
                ];
            }
        }

        // Generate markdown documentation
        $this->generateMarkdown($documentation);
    }
}
```


## Data Models

### Filter Configuration Structure

```php
[
    'filter' => [
        'course_id' => '1',                    // exact match
        'status' => 'published',               // exact match
        'priority' => ['high', 'urgent'],      // in operator
        'created_at' => 'gte:2025-01-01',     // operator syntax
        'views_count' => 'between:100,1000',   // between operator
    ],
    'sort' => '-created_at',                   // - prefix for desc
    'page' => 1,
    'per_page' => 15,
    'search' => 'keyword',
]
```

### Supported Filter Operators

| Operator | SQL Equivalent | Example Usage | Description |
|----------|---------------|---------------|-------------|
| `eq` | `=` | `filter[status]=eq:published` | Exact match (default) |
| `neq` | `!=` | `filter[status]=neq:draft` | Not equal |
| `gt` | `>` | `filter[views]=gt:100` | Greater than |
| `gte` | `>=` | `filter[views]=gte:100` | Greater than or equal |
| `lt` | `<` | `filter[views]=lt:1000` | Less than |
| `lte` | `<=` | `filter[views]=lte:1000` | Less than or equal |
| `like` | `LIKE` | `filter[title]=like:%keyword%` | Partial match |
| `in` | `IN` | `filter[status]=in:draft,published` | Multiple values |
| `between` | `BETWEEN` | `filter[created_at]=between:2025-01-01,2025-12-31` | Range |


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Filter whitelist enforcement

*For any* API request with filter parameters, only filter fields that are in the repository's allowedFilters list should be processed, and any invalid filter field should result in a 400 error with a clear message listing allowed filters.

**Validates: Requirements 2.1, 2.2, 2.3**

### Property 2: Sort whitelist enforcement

*For any* API request with sort parameter, only sort fields that are in the repository's allowedSorts list should be accepted, and any invalid sort field should result in a 400 error with a clear message listing allowed sorts.

**Validates: Requirements 3.1, 3.2, 3.3**

### Property 3: Sort direction validation

*For any* API request with sort parameter, the sort direction (when explicitly specified) must be either 'asc' or 'desc', and any other value should be rejected or normalized to the default direction.

**Validates: Requirements 3.4**

### Property 4: Default sort application

*For any* API request without a sort parameter, the system should apply the repository's defaultSort value to ensure consistent ordering.

**Validates: Requirements 3.5**

### Property 5: Repository filter configuration completeness

*For any* repository that extends BaseRepository and uses FilterableRepository trait, it should have non-empty allowedFilters and allowedSorts arrays defined.

**Validates: Requirements 2.1, 3.1**


## Repetitive Code Patterns Identified

### Pattern 1: Filter Parameter Extraction in Controllers

**Found in:**
- `AnnouncementController::index()`
- `NewsController::index()`
- `SearchController::search()`
- `ContentStatisticsController::index()`

**Repetitive Code:**
```php
$filters = [
    'course_id' => $request->input('filter.course_id'),
    'priority' => $request->input('filter.priority'),
    'per_page' => $request->input('per_page', 15),
];
```

**Solution:** Extract to `HandlesFiltering` trait with `extractFilterParams()` method.

### Pattern 2: Authorization Checks in Controllers

**Found in:**
- `AssignmentController::store()` - `userCanManageCourse()`
- `AssignmentController::update()` - `userCanManageCourse()`
- `AssignmentController::destroy()` - `userCanManageCourse()`

**Repetitive Code:**
```php
if (! $this->userCanManageCourse($user, $course)) {
    return $this->error('Anda tidak memiliki akses...', 403);
}
```

**Solution:** Already using `ManagesCourse` trait, but should use Policy-based authorization with `$this->authorize()` instead.

### Pattern 3: Resource Loading with Relationships

**Found in:**
- `AnnouncementController::show()` - `->with(['author', 'course', 'revisions.editor'])`
- `NewsController::update()` - `->load(['author', 'categories', 'tags'])`
- `AssignmentController::show()` - `->load(['creator:id,name,email', 'lesson:id,title,slug'])`

**Repetitive Code:**
```php
$model->load(['relation1', 'relation2']);
```

**Solution:** Define default relationships in Repository or use Resource classes for consistent loading.

### Pattern 4: Try-Catch Error Handling

**Found in:**
- `AnnouncementController::store()`
- `AnnouncementController::update()`
- `AnnouncementController::schedule()`

**Repetitive Code:**
```php
try {
    $result = $this->service->someMethod();
    return $this->success($result, 'Success message');
} catch (\Exception $e) {
    return $this->error($e->getMessage(), 422);
}
```

**Solution:** Let exceptions bubble to global exception handler, or create a `handleServiceCall()` helper method.


## Error Handling

### Filter/Sort Validation Errors

When invalid filters or sorts are provided, the system should return:

```json
{
    "success": false,
    "message": "Invalid filter fields: invalid_field. Allowed filters: course_id, priority, status, created_at",
    "data": null,
    "meta": null,
    "errors": {
        "filter": ["Invalid filter fields: invalid_field"]
    }
}
```

HTTP Status: 400 Bad Request

### Invalid Operator Errors

When an unsupported operator is used:

```json
{
    "success": false,
    "message": "Invalid filter operator: invalid_op. Supported operators: eq, neq, gt, gte, lt, lte, like, in, between",
    "data": null,
    "meta": null,
    "errors": {
        "filter": ["Invalid operator: invalid_op"]
    }
}
```

HTTP Status: 400 Bad Request

## Testing Strategy

### Dual Testing Approach

Testing akan menggunakan kombinasi unit tests dan property-based tests:

1. **Unit Tests**: Verify specific filter/sort examples work correctly
2. **Property-Based Tests**: Verify filter/sort validation works across all inputs

### Property-Based Testing Library

**Library**: [Pest PHP](https://pestphp.com/) with data generators

Pest sudah digunakan di project ini.

### Test Structure

```
tests/
├── Feature/
│   └── Api/
│       ├── FilterValidationTest.php      # Property 1, 2
│       ├── SortValidationTest.php        # Property 3, 4
│       └── RepositoryConfigTest.php      # Property 5
└── Unit/
    └── Support/
        ├── QueryFilterTest.php
        └── HandlesFilteringTraitTest.php
```

### Property Test Configuration

- Each property-based test should run minimum 100 iterations
- Each test must be tagged with: `**Feature: code-reusability-and-filter-standardization, Property {number}: {property_text}**`
- Tests must reference the requirement they validate


## Implementation Approach

### Phase 1: Standardize Filter/Sort Infrastructure

1. Enhance `QueryFilter` class with validation
2. Create `HandlesFiltering` trait for controllers
3. Update `BaseRepository` to enforce filter/sort configuration
4. Create custom exceptions for filter/sort validation errors

### Phase 2: Migrate Repositories to Standard Approach

1. Identify all repositories using Spatie QueryBuilder
2. Migrate them to use custom QueryFilter
3. Define `allowedFilters`, `allowedSorts`, `defaultSort` for each repository
4. Remove Spatie QueryBuilder dependency if no longer needed

### Phase 3: Update Controllers

1. Add `HandlesFiltering` trait to controllers
2. Replace manual filter extraction with `extractFilterParams()`
3. Remove try-catch blocks where appropriate
4. Use Policy-based authorization instead of manual checks

### Phase 4: Extract Repetitive Code

1. Create shared validation rules for common patterns
2. Extract common query scopes to base models
3. Create resource classes for consistent relationship loading
4. Document all extracted patterns

### Phase 5: Generate Documentation

1. Create `docs:filters` command to generate filter/sort documentation
2. Generate markdown file with all allowed filters/sorts per endpoint
3. Update API documentation with filter/sort information
4. Create examples for common filter patterns

## Migration Strategy

### Backward Compatibility

During migration, support both old and new parameter formats:

- Old: `sort_by=created_at&sort_direction=desc`
- New: `sort=-created_at`

Use a middleware or request transformer to normalize old format to new format.

### Gradual Rollout

1. Start with new endpoints using the standardized approach
2. Migrate high-traffic endpoints first
3. Update documentation as endpoints are migrated
4. Deprecate old parameter format after migration is complete

