# Design Document - Code Quality Improvement

## Overview

This design document outlines the technical approach for improving code quality across the LMS Sertifikasi application. The improvements focus on standardization, maintainability, and adherence to Laravel best practices and SOLID principles.

## Architecture

### Layered Architecture Enhancement

```
┌─────────────────────────────────────────┐
│         HTTP Layer (Routes)              │
│  - API Versioning (v1, v2)              │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Controller Layer                    │
│  - Thin controllers                      │
│  - Dependency injection                  │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Validation Layer (NEW)              │
│  - Form Request classes                  │
│  - Custom validation rules               │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      DTO Layer (NEW)                     │
│  - Type-safe data transfer               │
│  - Immutable objects                     │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Service Layer                       │
│  - Business logic                        │
│  - Implements interfaces (NEW)           │
│  - Transaction management                │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Repository Layer                    │
│  - Data access                           │
│  - Query optimization                    │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Model Layer                         │
│  - Eloquent models                       │
│  - Relationships                         │
└─────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Base Form Request

**Location:** `app/Http/Requests/BaseFormRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Common messages
            'required' => __('validation.required'),
            'email' => __('validation.email'),
            'unique' => __('validation.unique'),
            // Add more common messages
        ];
    }
}
```

### 2. Custom Exception Hierarchy

**Location:** `app/Exceptions/`

```php
// Base Business Exception
abstract class BusinessException extends Exception
{
    protected int $statusCode = 400;
    protected string $errorCode;
    
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}

// Specific Exceptions
class ResourceNotFoundException extends BusinessException
{
    protected int $statusCode = 404;
    protected string $errorCode = 'RESOURCE_NOT_FOUND';
}

class ValidationException extends BusinessException
{
    protected int $statusCode = 422;
    protected string $errorCode = 'VALIDATION_ERROR';
}

class UnauthorizedException extends BusinessException
{
    protected int $statusCode = 401;
    protected string $errorCode = 'UNAUTHORIZED';
}

class ForbiddenException extends BusinessException
{
    protected int $statusCode = 403;
    protected string $errorCode = 'FORBIDDEN';
}

class InvalidPasswordException extends BusinessException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'INVALID_PASSWORD';
}

class DuplicateResourceException extends BusinessException
{
    protected int $statusCode = 409;
    protected string $errorCode = 'DUPLICATE_RESOURCE';
}
```

### 3. Data Transfer Objects (DTOs)

**Location:** `app/DTOs/`

```php
<?php

namespace App\DTOs;

abstract class BaseDTO
{
    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

// Example: Thread DTO
class CreateThreadDTO extends BaseDTO
{
    public function __construct(
        public readonly int $schemeId,
        public readonly string $title,
        public readonly string $content,
    ) {}
    
    public static function fromRequest(Request $request): self
    {
        return new self(
            schemeId: $request->input('scheme_id'),
            title: $request->input('title'),
            content: $request->input('content'),
        );
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            schemeId: $data['scheme_id'],
            title: $data['title'],
            content: $data['content'],
        );
    }
}
```

### 4. Service Interfaces

**Location:** `app/Contracts/Services/`

```php
<?php

namespace App\Contracts\Services;

interface ForumServiceInterface
{
    /**
     * Create a new thread.
     *
     * @param CreateThreadDTO $dto Thread creation data
     * @param User $user The authenticated user
     * @return Thread The created thread
     * @throws UnauthorizedException If user doesn't have permission
     * @throws ValidationException If data is invalid
     */
    public function createThread(CreateThreadDTO $dto, User $user): Thread;
    
    /**
     * Update an existing thread.
     *
     * @param Thread $thread The thread to update
     * @param UpdateThreadDTO $dto Update data
     * @return Thread The updated thread
     * @throws ForbiddenException If user is not the author
     */
    public function updateThread(Thread $thread, UpdateThreadDTO $dto): Thread;
    
    // ... other methods
}
```

### 5. Global Exception Handler

**Location:** `app/Exceptions/Handler.php`

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (BusinessException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => $e->getErrorCode(),
                    'errors' => null,
                ], $e->getStatusCode());
            }
        });
        
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                $statusCode = method_exists($e, 'getStatusCode') 
                    ? $e->getStatusCode() 
                    : 500;
                    
                return response()->json([
                    'success' => false,
                    'message' => app()->environment('production') 
                        ? __('errors.server_error')
                        : $e->getMessage(),
                    'error_code' => 'INTERNAL_SERVER_ERROR',
                    'errors' => app()->environment('production') ? null : [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ],
                ], $statusCode);
            }
        });
    }
}
```

## Data Models

### Translation Files Structure

**Location:** `lang/`

```
lang/
├── en/
│   ├── messages.php
│   ├── validation.php
│   ├── errors.php
│   └── auth.php
└── id/
    ├── messages.php
    ├── validation.php
    ├── errors.php
    └── auth.php
```

**Example:** `lang/en/messages.php`

```php
<?php

return [
    'success' => 'Success',
    'created' => 'Created successfully',
    'updated' => 'Updated successfully',
    'deleted' => 'Deleted successfully',
    'not_found' => 'Resource not found',
    'unauthorized' => 'Unauthorized access',
    'forbidden' => 'Access forbidden',
    'validation_failed' => 'Validation failed',
];
```

### Query Scopes and Optimization

**Example Model with Scopes:**

```php
<?php

namespace Modules\Forums\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Thread extends Model
{
    /**
     * Scope to eager load all necessary relations.
     */
    public function scopeWithFullDetails(Builder $query): Builder
    {
        return $query->with([
            'author:id,name,avatar_path',
            'scheme:id,title',
            'replies' => function ($query) {
                $query->with('author:id,name,avatar_path')
                      ->latest()
                      ->limit(5);
            },
            'reactions',
        ]);
    }
    
    /**
     * Scope for active threads only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->whereNull('closed_at');
    }
    
    /**
     * Scope for popular threads.
     */
    public function scopePopular(Builder $query, int $minViews = 100): Builder
    {
        return $query->where('views_count', '>=', $minViews)
                     ->orderBy('views_count', 'desc');
    }
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Validation Consistency
*For any* HTTP request requiring validation, when processed through Form Request classes, the validation rules should be applied consistently and return standardized error responses.
**Validates: Requirements 1.1, 1.2, 1.3**

### Property 2: Exception Type Mapping
*For any* business exception thrown, the HTTP status code returned should correctly correspond to the exception type (404 for ResourceNotFound, 401 for Unauthorized, etc.).
**Validates: Requirements 2.3, 8.3**

### Property 3: DTO Immutability
*For any* DTO instance created, all properties should be readonly and the DTO should be immutable after instantiation.
**Validates: Requirements 3.2, 3.3**

### Property 4: Interface Contract Compliance
*For any* service class implementing an interface, all interface methods should be implemented with matching signatures and return types.
**Validates: Requirements 4.1, 4.2**

### Property 5: Translation Key Existence
*For any* translation key used in the application, the key should exist in all supported language files (en, id).
**Validates: Requirements 6.1, 6.5**

### Property 6: Query N+1 Prevention
*For any* query loading related models, when using eager loading, the number of queries should not increase linearly with the number of parent records (N+1 problem should not occur).
**Validates: Requirements 7.1**

### Property 7: Error Response Consistency
*For any* API error response, the JSON structure should follow the standard format with success, message, error_code, and errors fields.
**Validates: Requirements 8.3, 8.4**

### Property 8: Code Deduplication
*For any* validation rule or business logic, if it appears in multiple places, it should be extracted into a reusable component (trait, service method, or validation rule).
**Validates: Requirements 9.1, 9.2, 9.3**

### Property 9: API Version Isolation
*For any* API endpoint, different versions should be isolated in separate route groups and controllers, preventing version conflicts.
**Validates: Requirements 10.1, 10.2**

### Property 10: PHPDoc Completeness
*For any* public method in service or repository classes, PHPDoc should include @param, @return, and @throws annotations.
**Validates: Requirements 5.1, 5.4**

## Error Handling

### Exception Handling Strategy

1. **Business Logic Errors**: Use custom exceptions (ResourceNotFoundException, ValidationException, etc.)
2. **Validation Errors**: Handle in Form Requests with standardized responses
3. **Database Errors**: Catch and wrap in appropriate business exceptions
4. **External Service Errors**: Wrap in custom exceptions with context
5. **Unexpected Errors**: Log with full context and return generic error to user

### Logging Strategy

```php
// In services
try {
    // Business logic
} catch (BusinessException $e) {
    Log::warning('Business exception occurred', [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'user_id' => auth()->id(),
        'request_id' => request()->header('X-Request-ID'),
    ]);
    throw $e;
} catch (Throwable $e) {
    Log::error('Unexpected error occurred', [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'user_id' => auth()->id(),
        'request_id' => request()->header('X-Request-ID'),
    ]);
    throw new InternalServerException('An unexpected error occurred');
}
```

## Testing Strategy

### Unit Testing

**Test Base Classes:**

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'TestDatabaseSeeder']);
    }
}
```

**Testing Form Requests:**

```php
public function test_validation_fails_with_invalid_data(): void
{
    $request = CreateThreadRequest::create('/api/threads', 'POST', [
        'title' => '', // Invalid: empty
        'content' => 'Test content',
    ]);
    
    $validator = Validator::make($request->all(), $request->rules());
    
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('title', $validator->errors()->toArray());
}
```

**Testing DTOs:**

```php
public function test_dto_is_immutable(): void
{
    $dto = new CreateThreadDTO(
        schemeId: 1,
        title: 'Test',
        content: 'Content'
    );
    
    $this->expectException(Error::class);
    $dto->title = 'New Title'; // Should fail - readonly property
}
```

**Testing Exceptions:**

```php
public function test_exception_returns_correct_status_code(): void
{
    $exception = new ResourceNotFoundException('Thread not found');
    
    $this->assertEquals(404, $exception->getStatusCode());
    $this->assertEquals('RESOURCE_NOT_FOUND', $exception->getErrorCode());
}
```

**Testing Services with Interfaces:**

```php
public function test_service_implements_interface(): void
{
    $service = app(ForumServiceInterface::class);
    
    $this->assertInstanceOf(ForumServiceInterface::class, $service);
    $this->assertTrue(method_exists($service, 'createThread'));
}
```

### Integration Testing

**Testing API Endpoints:**

```php
public function test_api_returns_standardized_error_response(): void
{
    $response = $this->postJson('/api/v1/threads', [
        'title' => '', // Invalid
    ]);
    
    $response->assertStatus(422)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'errors',
             ])
             ->assertJson([
                 'success' => false,
             ]);
}
```

**Testing Query Optimization:**

```php
public function test_eager_loading_prevents_n_plus_1(): void
{
    Thread::factory()->count(10)->create();
    
    DB::enableQueryLog();
    
    $threads = Thread::with('author', 'scheme')->get();
    
    $queries = DB::getQueryLog();
    
    // Should be 3 queries: threads, authors, schemes
    $this->assertCount(3, $queries);
}
```

### Performance Testing

```php
public function test_query_execution_time_is_acceptable(): void
{
    Thread::factory()->count(1000)->create();
    
    $start = microtime(true);
    
    $threads = Thread::withFullDetails()->paginate(20);
    
    $duration = microtime(true) - $start;
    
    // Should complete in less than 100ms
    $this->assertLessThan(0.1, $duration);
}
```

## Implementation Notes

### Migration Strategy

1. **Phase 1: Foundation (Week 1)**
   - Create base classes (BaseFormRequest, BaseDTO, BusinessException)
   - Set up exception hierarchy
   - Configure global exception handler

2. **Phase 2: Validation Layer (Week 1-2)**
   - Create Form Request classes for existing endpoints
   - Migrate validation from services to Form Requests
   - Add custom validation rules

3. **Phase 3: DTOs (Week 2)**
   - Create DTO classes for complex data structures
   - Refactor services to use DTOs
   - Update tests

4. **Phase 4: Interfaces (Week 2-3)**
   - Define service interfaces
   - Update service provider bindings
   - Refactor controllers to use interfaces

5. **Phase 5: Localization (Week 3)**
   - Extract hardcoded strings to translation files
   - Create Indonesian translations
   - Update all responses to use trans() helper

6. **Phase 6: Documentation (Week 3-4)**
   - Add PHPDoc to all public methods
   - Document complex business logic
   - Generate API documentation

7. **Phase 7: Optimization (Week 4)**
   - Add query scopes
   - Implement eager loading
   - Add caching layer
   - Performance testing

### Backward Compatibility

- Maintain existing API responses during migration
- Use feature flags for gradual rollout
- Keep old code until new code is fully tested
- Document breaking changes in changelog

### Code Review Checklist

- [ ] All validation in Form Requests
- [ ] Custom exceptions used instead of generic Exception
- [ ] DTOs used for complex data transfer
- [ ] Services implement interfaces
- [ ] PHPDoc on all public methods
- [ ] Translation keys used instead of hardcoded strings
- [ ] Eager loading used where appropriate
- [ ] Tests cover new functionality
- [ ] No code duplication
- [ ] Performance acceptable
