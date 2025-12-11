# Repository Filter/Sort Configuration Audit

**Generated:** December 10, 2025  
**Purpose:** Document current filter/sort configurations across all repositories to identify inconsistencies and guide standardization efforts.

## Executive Summary

This audit analyzed **23 repositories** across the LMS project to understand their filtering and sorting approaches:

- **7 repositories (30%)** use Spatie QueryBuilder
- **1 repository (4%)** uses custom QueryFilter (BaseRepository)
- **15 repositories (65%)** have no standardized filtering

### Key Findings

1. **Inconsistent Approaches**: The project uses multiple filtering strategies without a unified standard
2. **Spatie Dependency**: 7 repositories depend on Spatie QueryBuilder, which needs migration to custom QueryFilter
3. **Missing Configurations**: 15 repositories lack any filter/sort configuration, limiting API flexibility
4. **No Validation**: Current implementations don't validate filter/sort parameters against whitelists

### Recommendations

1. Migrate all Spatie QueryBuilder repositories to custom QueryFilter
2. Add filter/sort configurations to repositories that need them
3. Implement validation in QueryFilter to reject invalid parameters
4. Standardize parameter naming across all endpoints

---

## 1. Repositories Using Spatie QueryBuilder (7)

These repositories need to be migrated to the custom QueryFilter approach for consistency.

### 1.1 ActivityLogRepository

**File:** `app/Repositories/ActivityLogRepository.php`  
**Namespace:** `App\Repositories`

**Current Configuration:**
- **Allowed Filters:** `log_name`, `event`, `subject_type`, `subject_id`, `causer_type`, `causer_id`
- **Allowed Sorts:** `id`, `created_at`, `event`, `log_name`
- **Default Sort:** `-created_at` (descending)
- **Has paginate():** ✓

**Migration Priority:** Medium  
**Notes:** Well-configured with comprehensive filters for activity log queries.

---

### 1.2 MasterDataRepository

**File:** `app/Repositories/MasterDataRepository.php`  
**Namespace:** `App\Repositories`

**Current Configuration:**
- **Allowed Filters:** `is_active`, `is_system`, `value`, `label`
- **Allowed Sorts:** `value`, `label`, `sort_order`, `created_at`, `updated_at`
- **Default Sort:** `sort_order` (ascending)
- **Has paginate():** ✓

**Migration Priority:** Medium  
**Notes:** Good configuration for master data lookups.

---

### 1.3 CategoryRepository

**File:** `Modules/Common/app/Repositories/CategoryRepository.php`  
**Namespace:** `Modules\Common\Repositories`

**Current Configuration:**
- **Allowed Filters:** `name`, `value`, `description`, `status`
- **Allowed Sorts:** `name`, `value`, `status`, `created_at`, `updated_at`
- **Default Sort:** `-created_at` (descending)
- **Has paginate():** ✓
- **Special Features:** Integrates Scout/Meilisearch for full-text search

**Migration Priority:** High  
**Notes:** Heavily used repository with Scout integration. Migration needs to preserve search functionality.

---

### 1.4 EnrollmentRepository

**File:** `Modules/Enrollments/app/Repositories/EnrollmentRepository.php`  
**Namespace:** `Modules\Enrollments\Repositories`  
**Implements:** `EnrollmentRepositoryInterface`

**Current Configuration:**
- **Allowed Filters:** `status`, `user_id`, `enrolled_at`, `completed_at`
- **Allowed Sorts:** `id`, `created_at`, `updated_at`, `status`, `enrolled_at`, `completed_at`, `progress_percent`
- **Default Sort:** `-created_at` (descending)
- **Has paginate():** ✗ (uses custom pagination methods)
- **Special Features:** Scout integration, multiple pagination methods (`paginateByCourse`, `paginateByCourseIds`, `paginateByUser`)

**Migration Priority:** High  
**Notes:** Complex repository with multiple pagination methods. Needs careful migration to preserve all functionality.

---

### 1.5 CourseRepository

**File:** `Modules/Schemes/app/Repositories/CourseRepository.php`  
**Namespace:** `Modules\Schemes\Repositories`  
**Implements:** `CourseRepositoryInterface`

**Current Configuration:**
- **Allowed Filters:** `status`, `level_tag`, `type`, `category_id`
- **Allowed Sorts:** `id`, `code`, `title`, `created_at`, `updated_at`, `published_at`
- **Default Sort:** `title` (ascending)
- **Has paginate():** ✓

**Migration Priority:** High  
**Notes:** Core repository for course management. High traffic endpoint.

---

### 1.6 LessonRepository

**File:** `Modules/Schemes/app/Repositories/LessonRepository.php`  
**Namespace:** `Modules\Schemes\Repositories`  
**Implements:** `LessonRepositoryInterface`

**Current Configuration:**
- **Allowed Filters:** `status`, `content_type`
- **Allowed Sorts:** `id`, `title`, `order`, `status`, `duration_minutes`, `created_at`, `updated_at`, `published_at`
- **Default Sort:** `order` (ascending)
- **Has paginate():** ✗

**Migration Priority:** High  
**Notes:** Core repository for lesson management.

---

### 1.7 UnitRepository

**File:** `Modules/Schemes/app/Repositories/UnitRepository.php`  
**Namespace:** `Modules\Schemes\Repositories`  
**Implements:** `UnitRepositoryInterface`

**Current Configuration:**
- **Allowed Filters:** `status`
- **Allowed Sorts:** `id`, `code`, `title`, `order`, `status`, `created_at`, `updated_at`
- **Default Sort:** `order` (ascending)
- **Has paginate():** ✗

**Migration Priority:** High  
**Notes:** Core repository for unit management. Limited filters - may need expansion.

---

## 2. Repositories Using Custom QueryFilter (1)

### 2.1 BaseRepository

**File:** `app/Repositories/BaseRepository.php`  
**Namespace:** `App\Repositories`  
**Implements:** `BaseRepositoryInterface`  
**Uses Trait:** `FilterableRepository`

**Current Configuration:**
- **Allowed Filters:** (empty array - must be defined in child classes)
- **Allowed Sorts:** `id`, `created_at`, `updated_at`
- **Default Sort:** `id` (ascending)
- **Has paginate():** ✓

**Status:** ✓ Already using custom QueryFilter  
**Notes:** Base class for all repositories. Child classes should override `$allowedFilters` and `$allowedSorts`.

---

## 3. Repositories Without Filtering (15)

These repositories currently have no standardized filtering mechanism. They need evaluation to determine if filtering should be added.

### 3.1 AuthRepository

**File:** `Modules/Auth/app/Repositories/AuthRepository.php`  
**Namespace:** `Modules\Auth\Repositories`  
**Implements:** `AuthRepositoryInterface`

**Current State:**
- No filter/sort configuration
- No paginate() method
- Primarily used for authentication lookups

**Recommendation:** No filtering needed - authentication repositories typically don't require pagination/filtering.

---

### 3.2 AnnouncementRepository

**File:** `Modules/Content/app/Repositories/AnnouncementRepository.php`  
**Namespace:** `Modules\Content\Repositories`  
**Implements:** `AnnouncementRepositoryInterface`

**Current State:**
- No filter/sort configuration
- Custom filter methods: `getAnnouncementsForUser`, `getAnnouncementsForCourse`
- Manual filtering in methods (course_id, priority, unread)
- Manual sorting by priority and published_at

**Recommendation:** **HIGH PRIORITY** - Add standardized filtering. Current manual approach should be replaced with:
- **Suggested Filters:** `course_id`, `priority`, `status`, `created_at`, `published_at`
- **Suggested Sorts:** `id`, `priority`, `published_at`, `created_at`
- **Default Sort:** `-published_at`

---

### 3.3 ContentStatisticsRepository

**File:** `Modules/Content/app/Repositories/ContentStatisticsRepository.php`  
**Namespace:** `Modules\Content\Repositories`

**Current State:**
- No filter/sort configuration
- Statistics methods: `getAnnouncementStatistics`, `getNewsStatistics`

**Recommendation:** No filtering needed - statistics repositories typically aggregate data without pagination.

---

### 3.4 NewsRepository

**File:** `Modules/Content/app/Repositories/NewsRepository.php`  
**Namespace:** `Modules\Content\Repositories`  
**Implements:** `NewsRepositoryInterface`

**Current State:**
- No filter/sort configuration
- Custom filter methods: `getNewsFeed`, `searchNews`
- Manual filtering logic

**Recommendation:** **HIGH PRIORITY** - Add standardized filtering:
- **Suggested Filters:** `status`, `category_id`, `tag_id`, `author_id`, `published_at`, `created_at`
- **Suggested Sorts:** `id`, `published_at`, `views_count`, `created_at`
- **Default Sort:** `-published_at`

---

### 3.5 ForumStatisticsRepository

**File:** `Modules/Forums/app/Repositories/ForumStatisticsRepository.php`  
**Namespace:** `Modules\Forums\Repositories`

**Current State:**
- No filter/sort configuration
- Statistics repository

**Recommendation:** No filtering needed - statistics repository.

---

### 3.6 ReplyRepository

**File:** `Modules/Forums/app/Repositories/ReplyRepository.php`  
**Namespace:** `Modules\Forums\Repositories`

**Current State:**
- No filter/sort configuration
- No paginate() method

**Recommendation:** **MEDIUM PRIORITY** - Consider adding filtering:
- **Suggested Filters:** `thread_id`, `user_id`, `created_at`
- **Suggested Sorts:** `id`, `created_at`, `votes_count`
- **Default Sort:** `created_at`

---

### 3.7 ThreadRepository

**File:** `Modules/Forums/app/Repositories/ThreadRepository.php`  
**Namespace:** `Modules\Forums\Repositories`

**Current State:**
- No filter/sort configuration
- Custom filter method: `getThreadsForScheme`

**Recommendation:** **HIGH PRIORITY** - Add standardized filtering:
- **Suggested Filters:** `scheme_id`, `user_id`, `status`, `is_pinned`, `created_at`
- **Suggested Sorts:** `id`, `created_at`, `updated_at`, `replies_count`, `views_count`
- **Default Sort:** `-updated_at`

---

### 3.8 ChallengeRepository

**File:** `Modules/Gamification/app/Repositories/ChallengeRepository.php`  
**Namespace:** `Modules\Gamification\Repositories`  
**Implements:** `ChallengeRepositoryInterface`

**Current State:**
- No filter/sort configuration
- Has paginate() method

**Recommendation:** **MEDIUM PRIORITY** - Add filtering:
- **Suggested Filters:** `status`, `type`, `difficulty`, `points_min`, `points_max`
- **Suggested Sorts:** `id`, `title`, `points`, `created_at`
- **Default Sort:** `-created_at`

---

### 3.9 GamificationRepository

**File:** `Modules/Gamification/app/Repositories/GamificationRepository.php`  
**Namespace:** `Modules\Gamification\Repositories`  
**Implements:** `GamificationRepositoryInterface`

**Current State:**
- No filter/sort configuration

**Recommendation:** Evaluate based on actual usage patterns.

---

### 3.10 GradingRepository

**File:** `Modules/Grading/app/Repositories/GradingRepository.php`  
**Namespace:** `Modules\Grading\Repositories`  
**Implements:** `GradingRepositoryInterface`

**Current State:**
- No filter/sort configuration
- Has paginate() method

**Recommendation:** **HIGH PRIORITY** - Add filtering:
- **Suggested Filters:** `assignment_id`, `student_id`, `grader_id`, `status`, `grade_min`, `grade_max`
- **Suggested Sorts:** `id`, `submitted_at`, `graded_at`, `grade`
- **Default Sort:** `-submitted_at`

---

### 3.11 AssignmentRepository

**File:** `Modules/Learning/app/Repositories/AssignmentRepository.php`  
**Namespace:** `Modules\Learning\Repositories`  
**Implements:** `AssignmentRepositoryInterface`

**Current State:**
- No filter/sort configuration
- Custom filter method: `listForLesson`

**Recommendation:** **HIGH PRIORITY** - Add standardized filtering:
- **Suggested Filters:** `lesson_id`, `status`, `type`, `due_date`, `created_at`
- **Suggested Sorts:** `id`, `title`, `due_date`, `order`, `created_at`
- **Default Sort:** `order`

---

### 3.12 LearningRepository

**File:** `Modules/Learning/app/Repositories/LearningRepository.php`  
**Namespace:** `Modules\Learning\Repositories`

**Current State:**
- No filter/sort configuration
- Appears to be a placeholder/template repository

**Recommendation:** Evaluate based on actual implementation.

---

### 3.13 SubmissionRepository

**File:** `Modules/Learning/app/Repositories/SubmissionRepository.php`  
**Namespace:** `Modules\Learning\Repositories`

**Current State:**
- No filter/sort configuration
- Custom filter method: `listForAssignment`

**Recommendation:** **HIGH PRIORITY** - Add standardized filtering:
- **Suggested Filters:** `assignment_id`, `user_id`, `status`, `submitted_at`, `graded`
- **Suggested Sorts:** `id`, `submitted_at`, `grade`, `created_at`
- **Default Sort:** `-submitted_at`

---

### 3.14 NotificationsRepository

**File:** `Modules/Notifications/app/Repositories/NotificationsRepository.php`  
**Namespace:** `Modules\Notifications\Repositories`

**Current State:**
- No filter/sort configuration

**Recommendation:** **MEDIUM PRIORITY** - Add filtering:
- **Suggested Filters:** `user_id`, `type`, `read`, `created_at`
- **Suggested Sorts:** `id`, `created_at`, `read_at`
- **Default Sort:** `-created_at`

---

### 3.15 OperationsRepository

**File:** `Modules/Operations/app/Repositories/OperationsRepository.php`  
**Namespace:** `Modules\Operations\Repositories`

**Current State:**
- No filter/sort configuration
- Appears to be a placeholder/template repository

**Recommendation:** Evaluate based on actual implementation.

---

## 4. Identified Inconsistencies

### 4.1 Parameter Naming

- **Spatie repositories** use `filter[field_name]` syntax
- **Custom implementations** may use different conventions
- **Sort parameter:** Some use `sort`, others might use `sort_by` and `sort_direction`

**Action Required:** Standardize on `filter[field_name]` and `sort` (with `-` prefix for descending).

### 4.2 Default Sort Behavior

- **Spatie repositories:** Explicitly define default sort
- **BaseRepository:** Defaults to `id` ascending
- **Custom implementations:** Inconsistent or undefined

**Action Required:** Ensure all repositories define meaningful default sorts.

### 4.3 Filter Validation

- **Current state:** No validation of filter/sort parameters
- **Risk:** Invalid parameters are silently ignored or cause errors

**Action Required:** Implement validation in QueryFilter to reject invalid parameters with clear error messages.

### 4.4 Scout/Meilisearch Integration

- **CategoryRepository** and **EnrollmentRepository** integrate Scout for full-text search
- **Implementation:** Uses `filter[search]` parameter

**Action Required:** Ensure custom QueryFilter supports Scout integration pattern.

### 4.5 Missing Configurations

15 repositories lack any filter/sort configuration, limiting API flexibility and consistency.

**Action Required:** Evaluate each repository and add appropriate configurations where needed.

---

## 5. Migration Roadmap

### Phase 1: Infrastructure (Completed)
- ✓ Enhanced QueryFilter with validation
- ✓ Created HandlesFiltering trait
- ✓ Created custom exceptions

### Phase 2: Audit (Current)
- ✓ Created analysis script
- ✓ Generated this audit document

### Phase 3: Migration (Next Steps)

#### High Priority Repositories
1. **CourseRepository** - Core functionality, high traffic
2. **EnrollmentRepository** - Complex, multiple pagination methods
3. **CategoryRepository** - Scout integration
4. **LessonRepository** - Core functionality
5. **UnitRepository** - Core functionality
6. **AnnouncementRepository** - Manual filtering needs standardization
7. **NewsRepository** - Manual filtering needs standardization
8. **ThreadRepository** - Forum functionality
9. **GradingRepository** - Critical for grading workflow
10. **AssignmentRepository** - Critical for learning workflow
11. **SubmissionRepository** - Critical for learning workflow

#### Medium Priority Repositories
1. **ActivityLogRepository** - Admin functionality
2. **MasterDataRepository** - Lookup data
3. **ReplyRepository** - Forum functionality
4. **ChallengeRepository** - Gamification
5. **NotificationsRepository** - User notifications

#### Low Priority / Evaluate
1. **AuthRepository** - Likely doesn't need filtering
2. **ContentStatisticsRepository** - Statistics, no pagination
3. **ForumStatisticsRepository** - Statistics, no pagination
4. **GamificationRepository** - Evaluate usage
5. **LearningRepository** - Placeholder
6. **OperationsRepository** - Placeholder

---

## 6. Next Steps

1. **Review this audit** with the development team
2. **Prioritize repositories** for migration based on usage and impact
3. **Begin migration** starting with high-priority repositories
4. **Update controllers** to use HandlesFiltering trait
5. **Generate API documentation** showing available filters/sorts per endpoint
6. **Write tests** to ensure filter/sort validation works correctly

---

## Appendix: Filter/Sort Configuration Template

For repositories that need filtering added, use this template:

```php
class ExampleRepository extends BaseRepository
{
    protected array $allowedFilters = [
        'field1',
        'field2',
        'status',
        'created_at',
    ];

    protected array $allowedSorts = [
        'id',
        'field1',
        'field2',
        'created_at',
        'updated_at',
    ];

    protected string $defaultSort = '-created_at';

    public function paginate(array $params, int $perPage = 15): LengthAwarePaginator
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

---

**End of Audit Report**
