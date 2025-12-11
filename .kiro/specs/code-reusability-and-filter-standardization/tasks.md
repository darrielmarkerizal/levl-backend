# Implementation Plan

## Phase 1: Enhance Filter/Sort Infrastructure ✅

- [x] 1. Enhance QueryFilter class with validation
  - [x] 1.1 Add validateFilters() method to QueryFilter
  - [x] 1.2 Add validateSorts() method to QueryFilter
  - [x] 1.3 Add validateSortDirection() method
  - [x] 1.4 Update apply() method to call validation methods
  - [ ]* 1.5 Write property test for filter validation
    - **Property 1: Filter whitelist enforcement**
    - **Validates: Requirements 2.1, 2.2, 2.3**
  - [ ]* 1.6 Write property test for sort validation
    - **Property 2: Sort whitelist enforcement**
    - **Validates: Requirements 3.1, 3.2, 3.3**

- [x] 2. Create HandlesFiltering trait for controllers
  - [x] 2.1 Create app/Support/Traits/HandlesFiltering.php
  - [ ]* 2.2 Write unit tests for HandlesFiltering trait

- [x] 3. Create custom exceptions for validation errors
  - [x] 3.1 Create app/Exceptions/InvalidFilterException.php
  - [x] 3.2 Create app/Exceptions/InvalidSortException.php
  - [x] 3.3 Update Handler.php to handle new exceptions

## Phase 2: Audit and Document Current Filter/Sort Usage ✅

- [x] 4. Audit all repositories for filter/sort configuration
  - [x] 4.1 Create analysis script to scan all repositories
  - [x] 4.2 Document current filter/sort configurations

- [x] 5. Create filter/sort documentation generator
  - [x] 5.1 Create app/Console/Commands/GenerateFilterDocumentation.php
  - [x] 5.2 Generate initial filter/sort documentation

## Phase 3: Standardize Repository Implementations ✅

- [x] 6. Update BaseRepository with filter/sort enforcement
  - [x] 6.1 Add validation to BaseRepository
  - [ ]* 6.2 Write property test for repository configuration
    - **Property 5: Repository filter configuration completeness**
    - **Validates: Requirements 2.1, 3.1**

- [x] 7. Migrate Spatie QueryBuilder repositories to custom QueryFilter
  - [x] 7.1 Update CategoryRepository
  - [x] 7.2 Update EnrollmentRepository
  - [x] 7.3 Update UnitRepository
  - [x] 7.4 Update LessonRepository
  - [x] 7.5 Update CourseRepository
  - [x] 7.6 Update ActivityLogRepository
  - [x] 7.7 Update MasterDataRepository


## Phase 4: Update Controllers to Use Standard Approach

- [x] 8. Update Content module controllers
  - [x] 8.1 Update AnnouncementController
    - Add HandlesFiltering trait
    - Replace manual filter extraction with extractFilterParams()
    - Update index() method to use trait method
    - _Requirements: 1.1, 1.3_
  - [x] 8.2 Update NewsController
    - Add HandlesFiltering trait
    - Replace manual filter extraction with extractFilterParams()
    - Update index() method to use trait method
    - _Requirements: 1.1, 1.3_
  - [x] 8.3 Update SearchController (Content)
    - Add HandlesFiltering trait
    - Replace manual filter extraction with extractFilterParams()
    - Standardize parameter naming
    - _Requirements: 1.1, 1.3_
  - [x] 8.4 Update ContentStatisticsController
    - Add HandlesFiltering trait
    - Replace manual filter extraction with extractFilterParams()
    - Update index() method to use trait method
    - _Requirements: 1.1, 1.3_
  - [x] 8.5 Update CourseAnnouncementController
    - Add HandlesFiltering trait
    - Replace manual filter extraction with extractFilterParams()
    - Update index() method to use trait method
    - _Requirements: 1.1, 1.3_

- [x] 9. Update Search module controllers
  - [x] 9.1 Update SearchController (Search module)
    - Add HandlesFiltering trait
    - Replace manual filter extraction with extractFilterParams()
    - Standardize parameter naming (sort_by -> sort)
    - _Requirements: 1.1, 1.3_

- [x] 10. Update Learning module controllers
  - [x] 10.1 Update SubmissionController
    - Add HandlesFiltering trait
    - Replace manual filter extraction with extractFilterParams()
    - Update index() method to use trait method
    - _Requirements: 1.1, 1.3_

- [x] 11. Update Common module controllers
  - [x] 11.1 Update CategoriesController
    - Add HandlesFiltering trait
    - Replace manual filter extraction with extractFilterParams()
    - Update index() method to use trait method
    - _Requirements: 1.1, 1.3_

- [x] 12. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.


## Phase 5: Extract Repetitive Code Patterns

- [x] 13. Extract authorization check patterns
  - [x] 13.1 Review authorization checks in AssignmentController
    - Identify manual authorization checks using userCanManageCourse()
    - Create Policy methods to replace manual checks
    - _Requirements: 1.5_
  - [x] 13.2 Create AssignmentPolicy if not exists
    - Add create(), update(), delete() methods
    - Use ManagesCourse trait logic in policy
    - _Requirements: 1.5_
  - [x] 13.3 Replace manual checks with $this->authorize()
    - Update store() method to use $this->authorize('create')
    - Update update() method to use $this->authorize('update')
    - Update destroy() method to use $this->authorize('delete')
    - Remove manual error responses
    - _Requirements: 1.5_

- [x] 14. Extract try-catch error handling patterns
  - [x] 14.1 Review try-catch blocks in controllers
    - Identify controllers with repetitive try-catch patterns
    - Determine which exceptions should bubble to global handler
    - _Requirements: 1.1_
  - [x] 14.2 Remove unnecessary try-catch blocks
    - Remove try-catch from AnnouncementController methods
    - Remove try-catch from NewsController methods
    - Let exceptions bubble to global exception handler
    - _Requirements: 1.1_
  - [x] 14.3 Update global exception handler if needed
    - Ensure Handler.php properly handles service exceptions
    - Return consistent error format using ApiResponse
    - _Requirements: 1.1_

- [x] 15. Extract resource loading patterns
  - [x] 15.1 Identify common relationship loading patterns
    - Review controllers for repeated ->load() or ->with() calls
    - Document common relationship combinations
    - _Requirements: 1.1, 1.3_
  - [x] 15.2 Create API Resource classes for consistent loading
    - Create AnnouncementResource with default relationships
    - Create NewsResource with default relationships
    - Create AssignmentResource with default relationships
    - _Requirements: 1.3_
  - [x] 15.3 Update controllers to use Resource classes
    - Replace manual ->load() with Resource::make() or Resource::collection()
    - Ensure consistent relationship loading across endpoints
    - _Requirements: 1.3_

- [x] 16. Extract validation rule patterns
  - [x] 16.1 Identify duplicate validation rules
    - Scan FormRequest classes for duplicate rules
    - Identify common validation patterns
    - _Requirements: 1.2_
  - [x] 16.2 Create shared validation rule classes
    - Create custom validation rules for common patterns
    - Extract to app/Rules/ directory
    - _Requirements: 1.2_
  - [x] 16.3 Update FormRequest classes to use shared rules
    - Replace duplicate rules with shared rule classes
    - Ensure validation messages are consistent
    - _Requirements: 1.2_

- [x] 17. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.


## Phase 6: Property-Based Tests

- [x]* 18. Write property tests for filter validation
  - [ ]* 18.1 Create tests/Feature/Api/FilterValidationTest.php
    - **Property 1: Filter whitelist enforcement**
    - **Validates: Requirements 2.1, 2.2, 2.3**
  - [ ]* 18.2 Write property test for default behavior
    - Test that empty filters return all data (paginated)
    - **Validates: Requirements 2.5**

- [x]* 19. Write property tests for sort validation
  - [ ]* 19.1 Create tests/Feature/Api/SortValidationTest.php
    - **Property 2: Sort whitelist enforcement**
    - **Property 3: Sort direction validation**
    - **Property 4: Default sort application**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

- [x]* 20. Write property tests for repository configuration
  - [ ]* 20.1 Create tests/Feature/Api/RepositoryConfigTest.php
    - **Property 5: Repository filter configuration completeness**
    - **Validates: Requirements 2.1, 3.1**

- [x] 21. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Phase 7: Documentation and Cleanup

- [x] 22. Update API documentation
  - [x] 22.1 Run filter documentation generator
    - Execute php artisan docs:filters
    - Review generated documentation
    - _Requirements: 4.5_
  - [x] 22.2 Update controller docblocks with filter/sort info
    - Add @queryParam annotations for all filters
    - Add @queryParam annotations for sort parameter
    - Include examples of filter operators
    - _Requirements: 4.1, 4.2, 4.3, 4.4_
  - [x] 22.3 Create filter usage guide
    - Create docs/FILTER_USAGE_GUIDE.md
    - Document all supported operators with examples
    - Document sort syntax with examples
    - Include common use cases
    - _Requirements: 4.3, 4.4_

- [x] 23. Create summary report
  - [x] 23.1 Document all extracted patterns
    - List all repetitive code patterns found
    - Document solutions implemented
    - List all repositories migrated
    - _Requirements: 1.1_
  - [x] 23.2 Create before/after comparison
    - Show code examples before and after refactoring
    - Highlight improvements in consistency
    - Document lines of code reduced
    - _Requirements: 1.1_

- [x] 24. Final Checkpoint - Complete
  - Ensure all tests pass, ask the user if questions arise.

