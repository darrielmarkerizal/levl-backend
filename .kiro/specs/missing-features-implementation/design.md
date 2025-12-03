# Design Document - Missing Features Implementation

## Overview

This design document outlines the technical approach for implementing missing features in the LMS Sertifikasi application, including advanced search, assessment registration, content workflow, file management, search history, and notification preferences.

## Architecture

### System Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    API Gateway Layer                     │
│  - Rate limiting                                         │
│  - Authentication                                        │
└────────────────────┬────────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
┌───────▼────────┐      ┌────────▼──────────┐
│  Search Module │      │  Assessment Module │
│  - Full-text   │      │  - Registration    │
│  - Filters     │      │  - Scheduling      │
│  - History     │      │  - Prerequisites   │
└────────────────┘      └───────────────────┘
        │                         │
┌───────▼────────┐      ┌────────▼──────────┐
│ Content Module │      │   File Module      │
│  - Workflow    │      │  - Library         │
│  - Approval    │      │  - Versioning      │
│  - Scheduling  │      │  - Quota           │
└────────────────┘      └───────────────────┘
        │                         │
┌───────▼────────┐      ┌────────▼──────────┐
│Notification Mod│      │  Recommendation    │
│  - Preferences │      │  - Collaborative   │
│  - Channels    │      │  - Content-based   │
└────────────────┘      └───────────────────┘
```

## Components and Interfaces

### 1. Advanced Search System

#### Search Service Interface

**Location:** `Modules/Search/app/Contracts/SearchServiceInterface.php`

```php
<?php

namespace Modules\Search\Contracts;

interface SearchServiceInterface
{
    /**
     * Perform full-text search with filters.
     *
     * @param string $query Search query
     * @param array $filters Filter criteria
     * @param array $sort Sorting options
     * @return SearchResultDTO
     */
    public function search(string $query, array $filters = [], array $sort = []): SearchResultDTO;
    
    /**
     * Get autocomplete suggestions.
     *
     * @param string $query Partial query
     * @param int $limit Number of suggestions
     * @return array
     */
    public function getSuggestions(string $query, int $limit = 10): array;
    
    /**
     * Save search query to history.
     *
     * @param User $user
     * @param string $query
     * @return void
     */
    public function saveSearchHistory(User $user, string $query): void;
}
```

#### Search Index Configuration

**Using Laravel Scout with Meilisearch:**

```php
// config/scout.php
'meilisearch' => [
    'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
    'key' => env('MEILISEARCH_KEY'),
],

// Modules/Schemes/app/Models/Course.php
use Laravel\Scout\Searchable;

class Course extends Model
{
    use Searchable;
    
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'level' => $this->level,
            'category' => $this->category->name,
            'instructor' => $this->instructor->name,
            'tags' => $this->tags->pluck('name')->toArray(),
            'status' => $this->status,
            'duration' => $this->duration,
            'popularity' => $this->enrollments_count,
        ];
    }
    
    public function searchableAs(): string
    {
        return 'courses_index';
    }
}
```

#### Search Filter Builder

```php
<?php

namespace Modules\Search\Services;

class SearchFilterBuilder
{
    protected array $filters = [];
    
    public function addCategoryFilter(array $categoryIds): self
    {
        $this->filters['category_id'] = $categoryIds;
        return $this;
    }
    
    public function addLevelFilter(array $levels): self
    {
        $this->filters['level'] = $levels;
        return $this;
    }
    
    public function addInstructorFilter(array $instructorIds): self
    {
        $this->filters['instructor_id'] = $instructorIds;
        return $this;
    }
    
    public function addDurationFilter(int $minDuration, int $maxDuration): self
    {
        $this->filters['duration'] = [
            'min' => $minDuration,
            'max' => $maxDuration,
        ];
        return $this;
    }
    
    public function addStatusFilter(array $statuses): self
    {
        $this->filters['status'] = $statuses;
        return $this;
    }
    
    public function build(): array
    {
        return $this->filters;
    }
}
```

### 2. Assessment Registration System

#### Assessment Registration Model

**Location:** `Modules/Assessments/app/Models/AssessmentRegistration.php`

```php
<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRegistration extends Model
{
    protected $fillable = [
        'user_id',
        'assessment_id',
        'enrollment_id',
        'scheduled_at',
        'status',
        'payment_status',
        'payment_amount',
        'payment_method',
        'payment_reference',
        'prerequisites_met',
        'prerequisites_checked_at',
        'confirmation_sent_at',
        'reminder_sent_at',
        'completed_at',
        'result',
        'score',
        'notes',
    ];
    
    protected $casts = [
        'scheduled_at' => 'datetime',
        'prerequisites_met' => 'boolean',
        'prerequisites_checked_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'payment_amount' => 'decimal:2',
    ];
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';
    
    // Payment status constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
    
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
```

#### Assessment Registration Service

```php
<?php

namespace Modules\Assessments\Services;

interface AssessmentRegistrationServiceInterface
{
    /**
     * Register user for assessment.
     *
     * @param User $user
     * @param Assessment $assessment
     * @param RegisterAssessmentDTO $dto
     * @return AssessmentRegistration
     * @throws PrerequisitesNotMetException
     * @throws AssessmentFullException
     */
    public function register(
        User $user, 
        Assessment $assessment, 
        RegisterAssessmentDTO $dto
    ): AssessmentRegistration;
    
    /**
     * Check if user meets prerequisites.
     *
     * @param User $user
     * @param Assessment $assessment
     * @return PrerequisiteCheckResult
     */
    public function checkPrerequisites(User $user, Assessment $assessment): PrerequisiteCheckResult;
    
    /**
     * Get available time slots.
     *
     * @param Assessment $assessment
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getAvailableSlots(
        Assessment $assessment, 
        Carbon $startDate, 
        Carbon $endDate
    ): Collection;
    
    /**
     * Cancel registration.
     *
     * @param AssessmentRegistration $registration
     * @param string $reason
     * @return bool
     * @throws CancellationNotAllowedException
     */
    public function cancel(AssessmentRegistration $registration, string $reason): bool;
}
```

### 3. Content Management Workflow

#### Content Workflow State Machine

```php
<?php

namespace Modules\Content\Services;

class ContentWorkflowService
{
    // Workflow states
    const STATE_DRAFT = 'draft';
    const STATE_SUBMITTED = 'submitted';
    const STATE_IN_REVIEW = 'in_review';
    const STATE_APPROVED = 'approved';
    const STATE_REJECTED = 'rejected';
    const STATE_SCHEDULED = 'scheduled';
    const STATE_PUBLISHED = 'published';
    const STATE_ARCHIVED = 'archived';
    
    // Allowed transitions
    protected array $transitions = [
        self::STATE_DRAFT => [self::STATE_SUBMITTED],
        self::STATE_SUBMITTED => [self::STATE_IN_REVIEW, self::STATE_DRAFT],
        self::STATE_IN_REVIEW => [self::STATE_APPROVED, self::STATE_REJECTED, self::STATE_DRAFT],
        self::STATE_APPROVED => [self::STATE_SCHEDULED, self::STATE_PUBLISHED, self::STATE_DRAFT],
        self::STATE_REJECTED => [self::STATE_DRAFT],
        self::STATE_SCHEDULED => [self::STATE_PUBLISHED, self::STATE_DRAFT],
        self::STATE_PUBLISHED => [self::STATE_ARCHIVED, self::STATE_DRAFT],
        self::STATE_ARCHIVED => [self::STATE_DRAFT],
    ];
    
    /**
     * Transition content to new state.
     *
     * @param Content $content
     * @param string $newState
     * @param User $user
     * @param string|null $note
     * @return bool
     * @throws InvalidTransitionException
     */
    public function transition(
        Content $content, 
        string $newState, 
        User $user, 
        ?string $note = null
    ): bool {
        if (!$this->canTransition($content->status, $newState)) {
            throw new InvalidTransitionException(
                "Cannot transition from {$content->status} to {$newState}"
            );
        }
        
        DB::transaction(function () use ($content, $newState, $user, $note) {
            // Save workflow history
            ContentWorkflowHistory::create([
                'content_type' => get_class($content),
                'content_id' => $content->id,
                'from_state' => $content->status,
                'to_state' => $newState,
                'user_id' => $user->id,
                'note' => $note,
            ]);
            
            // Update content status
            $content->update(['status' => $newState]);
            
            // Fire appropriate event
            $this->fireTransitionEvent($content, $newState, $user);
        });
        
        return true;
    }
    
    protected function canTransition(string $currentState, string $newState): bool
    {
        return in_array($newState, $this->transitions[$currentState] ?? []);
    }
}
```

### 4. Advanced File Management System

#### File Library Model

**Location:** `Modules/Files/app/Models/FileLibrary.php`

```php
<?php

namespace Modules\Files\Models;

class FileLibrary extends Model
{
    protected $fillable = [
        'user_id',
        'folder_id',
        'filename',
        'original_filename',
        'path',
        'mime_type',
        'size',
        'extension',
        'visibility',
        'is_shared',
        'shared_with',
        'version',
        'parent_file_id',
        'checksum',
        'metadata',
    ];
    
    protected $casts = [
        'size' => 'integer',
        'is_shared' => 'boolean',
        'shared_with' => 'array',
        'metadata' => 'array',
        'version' => 'integer',
    ];
    
    // Visibility constants
    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_SHARED = 'shared';
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function folder(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class, 'folder_id');
    }
    
    public function versions(): HasMany
    {
        return $this->hasMany(FileLibrary::class, 'parent_file_id')
                    ->orderBy('version', 'desc');
    }
    
    public function parentFile(): BelongsTo
    {
        return $this->belongsTo(FileLibrary::class, 'parent_file_id');
    }
}

class FileFolder extends Model
{
    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'path',
        'visibility',
    ];
    
    public function files(): HasMany
    {
        return $this->hasMany(FileLibrary::class, 'folder_id');
    }
    
    public function subfolders(): HasMany
    {
        return $this->hasMany(FileFolder::class, 'parent_id');
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class, 'parent_id');
    }
}
```

#### File Management Service

```php
<?php

namespace Modules\Files\Services;

interface FileManagementServiceInterface
{
    /**
     * Upload file to library.
     *
     * @param UploadedFile $file
     * @param User $user
     * @param int|null $folderId
     * @return FileLibrary
     * @throws StorageQuotaExceededException
     */
    public function upload(UploadedFile $file, User $user, ?int $folderId = null): FileLibrary;
    
    /**
     * Create new version of existing file.
     *
     * @param FileLibrary $file
     * @param UploadedFile $newFile
     * @return FileLibrary
     */
    public function createVersion(FileLibrary $file, UploadedFile $newFile): FileLibrary;
    
    /**
     * Share file with users.
     *
     * @param FileLibrary $file
     * @param array $userIds
     * @return bool
     */
    public function shareWith(FileLibrary $file, array $userIds): bool;
    
    /**
     * Check storage quota.
     *
     * @param User $user
     * @return StorageQuotaDTO
     */
    public function checkQuota(User $user): StorageQuotaDTO;
    
    /**
     * Move files to folder.
     *
     * @param array $fileIds
     * @param int $folderId
     * @return bool
     */
    public function moveToFolder(array $fileIds, int $folderId): bool;
}
```

### 5. Search History and Recommendations

#### Search History Model

```php
<?php

namespace Modules\Search\Models;

class SearchHistory extends Model
{
    protected $fillable = [
        'user_id',
        'query',
        'filters',
        'results_count',
        'clicked_result_id',
        'clicked_result_type',
    ];
    
    protected $casts = [
        'filters' => 'array',
        'results_count' => 'integer',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

#### Recommendation Service

```php
<?php

namespace Modules\Recommendations\Services;

interface RecommendationServiceInterface
{
    /**
     * Get personalized recommendations for user.
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getRecommendations(User $user, int $limit = 10): Collection;
    
    /**
     * Get similar courses based on content.
     *
     * @param Course $course
     * @param int $limit
     * @return Collection
     */
    public function getSimilarCourses(Course $course, int $limit = 5): Collection;
    
    /**
     * Get trending courses.
     *
     * @param int $limit
     * @param string $period (day, week, month)
     * @return Collection
     */
    public function getTrendingCourses(int $limit = 10, string $period = 'week'): Collection;
}
```

### 6. Notification Preferences

#### Notification Preference Model

```php
<?php

namespace Modules\Notifications\Models;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'channel',
        'enabled',
        'frequency',
    ];
    
    protected $casts = [
        'enabled' => 'boolean',
    ];
    
    // Categories
    const CATEGORY_COURSE_UPDATES = 'course_updates';
    const CATEGORY_ASSIGNMENTS = 'assignments';
    const CATEGORY_ASSESSMENTS = 'assessments';
    const CATEGORY_FORUM = 'forum';
    const CATEGORY_ACHIEVEMENTS = 'achievements';
    const CATEGORY_SYSTEM = 'system';
    
    // Channels
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_IN_APP = 'in_app';
    const CHANNEL_PUSH = 'push';
    
    // Frequency
    const FREQUENCY_IMMEDIATE = 'immediate';
    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## Data Models

### Database Schema

#### Assessment Registrations Table

```sql
CREATE TABLE assessment_registrations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    assessment_id BIGINT UNSIGNED NOT NULL,
    enrollment_id BIGINT UNSIGNED,
    scheduled_at TIMESTAMP NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_status VARCHAR(50) DEFAULT 'pending',
    payment_amount DECIMAL(10,2) NULL,
    payment_method VARCHAR(50) NULL,
    payment_reference VARCHAR(255) NULL,
    prerequisites_met BOOLEAN DEFAULT FALSE,
    prerequisites_checked_at TIMESTAMP NULL,
    confirmation_sent_at TIMESTAMP NULL,
    reminder_sent_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    result VARCHAR(50) NULL,
    score DECIMAL(5,2) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE SET NULL,
    
    INDEX idx_user_status (user_id, status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_status (status)
);
```

#### Content Workflow History Table

```sql
CREATE TABLE content_workflow_history (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    content_type VARCHAR(255) NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,
    from_state VARCHAR(50) NOT NULL,
    to_state VARCHAR(50) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_content (content_type, content_id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
);
```

#### File Library Tables

```sql
CREATE TABLE file_folders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    path VARCHAR(1000) NOT NULL,
    visibility VARCHAR(50) DEFAULT 'private',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES file_folders(id) ON DELETE CASCADE,
    
    INDEX idx_user_parent (user_id, parent_id),
    INDEX idx_path (path(255))
);

CREATE TABLE file_library (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    folder_id BIGINT UNSIGNED NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    path VARCHAR(1000) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size BIGINT UNSIGNED NOT NULL,
    extension VARCHAR(20) NOT NULL,
    visibility VARCHAR(50) DEFAULT 'private',
    is_shared BOOLEAN DEFAULT FALSE,
    shared_with JSON NULL,
    version INT DEFAULT 1,
    parent_file_id BIGINT UNSIGNED NULL,
    checksum VARCHAR(64) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (folder_id) REFERENCES file_folders(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_file_id) REFERENCES file_library(id) ON DELETE CASCADE,
    
    INDEX idx_user (user_id),
    INDEX idx_folder (folder_id),
    INDEX idx_parent (parent_file_id),
    INDEX idx_checksum (checksum)
);
```

#### Search History Table

```sql
CREATE TABLE search_history (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    query VARCHAR(500) NOT NULL,
    filters JSON NULL,
    results_count INT DEFAULT 0,
    clicked_result_id BIGINT UNSIGNED NULL,
    clicked_result_type VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_query (query(255))
);
```

#### Notification Preferences Table

```sql
CREATE TABLE notification_preferences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(50) NOT NULL,
    channel VARCHAR(50) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    frequency VARCHAR(50) DEFAULT 'immediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_category_channel (user_id, category, channel),
    INDEX idx_user (user_id)
);
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Search Result Relevance
*For any* search query with filters applied, all returned results should match the specified filter criteria.
**Validates: Requirements 1.3, 1.4**

### Property 2: Assessment Slot Capacity
*For any* assessment time slot, the number of registrations should never exceed the maximum capacity.
**Validates: Requirements 2.4**

### Property 3: Prerequisite Enforcement
*For any* assessment registration attempt, if prerequisites are not met, the registration should be rejected.
**Validates: Requirements 2.2**

### Property 4: Workflow State Transition Validity
*For any* content workflow state transition, only valid transitions as defined in the state machine should be allowed.
**Validates: Requirements 3.2, 3.3, 3.4**

### Property 5: File Version Lineage
*For any* file with versions, the version history should form a valid chain with incrementing version numbers.
**Validates: Requirements 4.6**

### Property 6: Storage Quota Enforcement
*For any* file upload attempt, if the user's storage quota would be exceeded, the upload should be rejected.
**Validates: Requirements 4.7**

### Property 7: Search History Uniqueness
*For any* user's search history, duplicate consecutive searches should be deduplicated.
**Validates: Requirements 6.1, 6.3**

### Property 8: Notification Preference Consistency
*For any* notification sent, it should only be delivered through channels that the user has enabled for that category.
**Validates: Requirements 6.2, 6.3**

### Property 9: File Sharing Permission
*For any* shared file access attempt, only users in the shared_with list should be able to access the file.
**Validates: Requirements 4.5**

### Property 10: Assessment Registration Cancellation Window
*For any* assessment registration cancellation attempt, it should only be allowed if within the specified timeframe before the scheduled date.
**Validates: Requirements 2.9**

## Error Handling

### Search Errors
- **No Results Found**: Return empty result set with suggestions
- **Invalid Filters**: Return validation error with valid filter options
- **Search Service Down**: Fallback to database search

### Assessment Registration Errors
- **Prerequisites Not Met**: Return detailed list of missing prerequisites
- **Slot Full**: Return alternative available slots
- **Payment Failed**: Retry mechanism with clear error message

### File Management Errors
- **Quota Exceeded**: Return current usage and quota limit
- **Invalid File Type**: Return list of allowed file types
- **Version Conflict**: Prompt user to resolve conflict

### Workflow Errors
- **Invalid Transition**: Return current state and allowed transitions
- **Permission Denied**: Return required permission level

## Testing Strategy

### Unit Tests

**Search Service Tests:**
```php
public function test_search_applies_filters_correctly(): void
{
    $courses = Course::factory()->count(10)->create([
        'level' => 'beginner',
    ]);
    
    $result = $this->searchService->search('', [
        'level' => ['beginner'],
    ]);
    
    $this->assertCount(10, $result->items);
    $this->assertTrue($result->items->every(fn($c) => $c->level === 'beginner'));
}
```

**Assessment Registration Tests:**
```php
public function test_cannot_register_without_prerequisites(): void
{
    $user = User::factory()->create();
    $assessment = Assessment::factory()->create([
        'prerequisites' => ['course_1_completed'],
    ]);
    
    $this->expectException(PrerequisitesNotMetException::class);
    
    $this->registrationService->register($user, $assessment, $dto);
}
```

**File Management Tests:**
```php
public function test_upload_fails_when_quota_exceeded(): void
{
    $user = User::factory()->create(['storage_quota' => 1000000]); // 1MB
    $file = UploadedFile::fake()->create('large.pdf', 2000); // 2MB
    
    $this->expectException(StorageQuotaExceededException::class);
    
    $this->fileService->upload($file, $user);
}
```

### Integration Tests

**Search Integration:**
```php
public function test_search_endpoint_returns_filtered_results(): void
{
    Course::factory()->count(5)->create(['level' => 'beginner']);
    Course::factory()->count(3)->create(['level' => 'advanced']);
    
    $response = $this->getJson('/api/v1/search/courses?query=&level[]=beginner');
    
    $response->assertStatus(200)
             ->assertJsonCount(5, 'data')
             ->assertJson([
                 'success' => true,
             ]);
}
```

**Assessment Registration Flow:**
```php
public function test_complete_assessment_registration_flow(): void
{
    $user = $this->actingAs(User::factory()->create());
    $assessment = Assessment::factory()->create();
    
    // Check prerequisites
    $response = $user->getJson("/api/v1/assessments/{$assessment->id}/prerequisites");
    $response->assertStatus(200)->assertJson(['prerequisites_met' => true]);
    
    // Get available slots
    $response = $user->getJson("/api/v1/assessments/{$assessment->id}/slots");
    $response->assertStatus(200)->assertJsonStructure(['data' => ['*' => ['datetime', 'available']]]);
    
    // Register
    $response = $user->postJson("/api/v1/assessments/{$assessment->id}/register", [
        'scheduled_at' => now()->addDays(7)->toDateTimeString(),
    ]);
    $response->assertStatus(201)->assertJson(['success' => true]);
}
```

## Implementation Notes

### Search Implementation with Meilisearch

1. Install Meilisearch and Laravel Scout
2. Configure searchable models
3. Index existing data
4. Implement search filters
5. Add autocomplete endpoint

### Assessment Registration Implementation

1. Create migration for assessment_registrations table
2. Implement prerequisite checking logic
3. Create slot availability calculator
4. Integrate payment gateway (if needed)
5. Set up notification triggers

### Content Workflow Implementation

1. Create workflow state machine
2. Implement transition validation
3. Add workflow history tracking
4. Create approval interface
5. Set up scheduled publishing

### File Management Implementation

1. Create file library tables
2. Implement folder structure
3. Add version control
4. Implement quota checking
5. Create file preview system

### Recommendation System Implementation

1. Track user interactions
2. Implement collaborative filtering
3. Add content-based filtering
4. Create trending algorithm
5. Cache recommendations

### Performance Considerations

- Index search queries for fast retrieval
- Cache frequently accessed data
- Use queue for heavy operations
- Implement pagination for large result sets
- Optimize database queries with eager loading
