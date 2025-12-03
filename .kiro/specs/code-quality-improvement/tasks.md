# Implementation Plan - Code Quality Improvement

- [x] 1. Set up foundation infrastructure
  - Create base classes and exception hierarchy
  - Set up global exception handler
  - Configure localization structure
  - _Requirements: 2.1, 2.2, 6.1_

- [x] 1.1 Create base Form Request class
  - Implement BaseFormRequest with standardized error handling
  - Add common validation messages
  - Set up JSON response format for validation errors
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 1.2 Create custom exception hierarchy
  - Implement BusinessException base class
  - Create ResourceNotFoundException
  - Create ValidationException
  - Create UnauthorizedException
  - Create ForbiddenException
  - Create InvalidPasswordException
  - Create DuplicateResourceException
  - _Requirements: 2.1, 2.2, 2.5_

- [x] 1.3 Configure global exception handler
  - Update Handler.php to catch BusinessException
  - Add JSON response formatting for API errors
  - Implement error logging with context
  - Add environment-based error detail exposure
  - _Requirements: 2.3, 2.4, 8.1, 8.3_

- [x] 1.4 Set up localization structure
  - Create lang/en directory with message files
  - Create lang/id directory with Indonesian translations
  - Add messages.php for common messages
  - Add validation.php for validation messages
  - Add errors.php for error messages
  - _Requirements: 6.1, 6.2, 6.5_

- [x] 1.5 Write unit tests for foundation
  - Test BaseFormRequest validation error format
  - Test custom exception status codes and error codes
  - Test global exception handler responses
  - Test translation key existence
  - _Requirements: 1.2, 2.3, 6.5_

- [ ] 2. Implement DTO pattern
  - Create base DTO class and example DTOs
  - Refactor existing services to use DTOs
  - _Requirements: 3.1, 3.2, 3.3_

- [ ] 2.1 Create base DTO class
  - Implement BaseDTO with toArray() method
  - Add documentation for DTO usage
  - _Requirements: 3.1_

- [ ] 2.2 Create DTOs for Forums module
  - Create CreateThreadDTO with readonly properties
  - Create UpdateThreadDTO
  - Create CreateReplyDTO
  - Add fromRequest() and fromArray() factory methods
  - _Requirements: 3.2, 3.3, 3.4_

- [ ] 2.3 Create DTOs for Content module
  - Create CreateAnnouncementDTO
  - Create UpdateAnnouncementDTO
  - Create CreateNewsDTO
  - Create UpdateNewsDTO
  - _Requirements: 3.2, 3.3, 3.4_

- [ ] 2.4 Create DTOs for Auth module
  - Create UpdateProfileDTO
  - Create ChangePasswordDTO
  - _Requirements: 3.2, 3.3, 3.4_

- [ ] 2.5 Write unit tests for DTOs
  - Test DTO immutability (readonly properties)
  - Test fromRequest() factory method
  - Test fromArray() factory method
  - Test toArray() conversion
  - _Requirements: 3.2, 3.3_

- [x] 3. Create service interfaces
  - Define interfaces for all service classes
  - Update service provider bindings
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 3.1 Create service interface contracts
  - Create app/Contracts/Services directory
  - Define ForumServiceInterface
  - Define ContentServiceInterface
  - Define ProfileServiceInterface
  - Add PHPDoc with @param, @return, @throws
  - _Requirements: 4.1, 4.4, 5.1_

- [x] 3.2 Implement interfaces in existing services
  - Update ForumService to implement ForumServiceInterface
  - Update ContentService to implement ContentServiceInterface
  - Update ProfileService to implement ProfileServiceInterface
  - _Requirements: 4.1, 4.2_

- [x] 3.3 Update service provider bindings
  - Bind interfaces to implementations in service providers
  - Update controllers to type-hint interfaces
  - _Requirements: 4.2, 4.3_

- [x] 3.4 Write tests for interface compliance
  - Test that services implement interfaces
  - Test that all interface methods exist
  - Test interface method signatures match
  - _Requirements: 4.1, 4.2_

- [ ] 4. Migrate validation to Form Requests
  - Create Form Request classes for existing endpoints
  - Remove validation logic from services
  - _Requirements: 1.1, 1.2, 1.4, 9.1_

- [ ] 4.1 Create Form Requests for Forums module
  - Create CreateThreadRequest extending BaseFormRequest
  - Create UpdateThreadRequest
  - Create CreateReplyRequest
  - Add validation rules and custom messages
  - _Requirements: 1.1, 1.3, 1.4_

- [ ] 4.2 Create Form Requests for Content module
  - Create CreateAnnouncementRequest
  - Create UpdateAnnouncementRequest
  - Create CreateNewsRequest
  - Create UpdateNewsRequest
  - _Requirements: 1.1, 1.3, 1.4_

- [ ] 4.3 Create Form Requests for Auth module
  - Create UpdateProfileRequest
  - Create ChangePasswordRequest
  - Create UploadAvatarRequest
  - _Requirements: 1.1, 1.3, 1.4_

- [ ] 4.4 Refactor services to remove validation
  - Remove Validator::make() calls from ForumService
  - Remove validation from ContentService
  - Remove validation from ProfileService
  - Services should now receive validated data from Form Requests
  - _Requirements: 1.1, 9.1, 9.2_

- [ ] 4.5 Write tests for Form Request validation
  - Test validation rules work correctly
  - Test validation error response format
  - Test custom validation messages
  - _Requirements: 1.2, 1.3_

- [ ] 5. Refactor services to use DTOs and exceptions
  - Update service methods to accept DTOs
  - Replace generic exceptions with custom exceptions
  - _Requirements: 2.1, 3.1, 8.1, 9.2_

- [ ] 5.1 Refactor ForumService
  - Update createThread() to accept CreateThreadDTO
  - Update updateThread() to accept UpdateThreadDTO
  - Replace generic Exception with custom exceptions
  - Update method signatures and PHPDoc
  - _Requirements: 2.1, 3.1, 5.1, 8.1_

- [ ] 5.2 Refactor ContentService
  - Update createAnnouncement() to accept CreateAnnouncementDTO
  - Update createNews() to accept CreateNewsDTO
  - Replace generic Exception with custom exceptions
  - Update method signatures and PHPDoc
  - _Requirements: 2.1, 3.1, 5.1, 8.1_

- [ ] 5.3 Refactor ProfileService
  - Update updateProfile() to accept UpdateProfileDTO
  - Update changePassword() to accept ChangePasswordDTO
  - Replace generic Exception with InvalidPasswordException
  - Update method signatures and PHPDoc
  - _Requirements: 2.1, 3.1, 5.1, 8.1_

- [ ] 5.4 Write integration tests for refactored services
  - Test services work with DTOs
  - Test custom exceptions are thrown correctly
  - Test exception status codes in API responses
  - _Requirements: 2.3, 3.1, 8.3_

- [ ] 6. Update controllers to use Form Requests and DTOs
  - Inject Form Requests in controller methods
  - Create DTOs from validated requests
  - Pass DTOs to services
  - _Requirements: 1.1, 3.1, 9.2_

- [ ] 6.1 Update Forums controllers
  - Inject CreateThreadRequest in ThreadController@store
  - Create CreateThreadDTO from request
  - Pass DTO to ForumService
  - Update other controller methods similarly
  - _Requirements: 1.1, 3.1_

- [ ] 6.2 Update Content controllers
  - Inject Form Requests in NewsController and AnnouncementController
  - Create DTOs from validated requests
  - Pass DTOs to ContentService
  - _Requirements: 1.1, 3.1_

- [ ] 6.3 Update Auth controllers
  - Inject Form Requests in ProfileController
  - Create DTOs from validated requests
  - Pass DTOs to ProfileService
  - _Requirements: 1.1, 3.1_

- [ ] 6.4 Write API tests for updated controllers
  - Test validation errors return 422 with proper format
  - Test successful requests work with new flow
  - Test custom exceptions return correct status codes
  - _Requirements: 1.2, 2.3, 8.3_

- [ ] 7. Add comprehensive PHPDoc documentation
  - Document all public methods in services
  - Document interfaces
  - Document DTOs and exceptions
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [ ] 7.1 Document service interfaces
  - Add PHPDoc to all interface methods
  - Include @param with types and descriptions
  - Include @return with type and description
  - Include @throws for all possible exceptions
  - _Requirements: 5.1, 5.4_

- [ ] 7.2 Document service implementations
  - Add PHPDoc to all public service methods
  - Document complex business logic with inline comments
  - Add class-level PHPDoc describing purpose
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 7.3 Document DTOs and exceptions
  - Add class-level PHPDoc for all DTOs
  - Document DTO properties and factory methods
  - Add PHPDoc for custom exception classes
  - _Requirements: 5.1, 5.3_

- [ ] 8. Implement query optimization
  - Add query scopes to models
  - Implement eager loading
  - Add caching for frequently accessed data
  - _Requirements: 7.1, 7.2, 7.3_

- [ ] 8.1 Add query scopes to models
  - Add withFullDetails() scope to Thread model
  - Add active() and popular() scopes
  - Add similar scopes to News and Announcement models
  - _Requirements: 7.2_

- [ ] 8.2 Implement eager loading in repositories
  - Update ThreadRepository to use eager loading
  - Update NewsRepository and AnnouncementRepository
  - Prevent N+1 queries with with() clauses
  - _Requirements: 7.1_

- [ ] 8.3 Add caching layer
  - Cache popular courses with Cache::remember()
  - Cache trending news
  - Cache user statistics
  - Set appropriate TTL for each cache
  - _Requirements: 7.3_

- [ ] 8.4 Write performance tests
  - Test N+1 query prevention with query log
  - Test cache hit/miss rates
  - Test query execution time is acceptable
  - _Requirements: 7.1, 7.3_

- [ ] 9. Replace hardcoded strings with translations
  - Extract all hardcoded messages to translation files
  - Update all responses to use trans() helper
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 9.1 Update API response messages
  - Replace hardcoded strings in ApiResponse trait
  - Use __() helper for all messages
  - Update success/error messages
  - _Requirements: 6.1, 6.2_

- [ ] 9.2 Update service layer messages
  - Replace exception messages with translation keys
  - Update validation messages
  - _Requirements: 6.1, 6.2_

- [ ] 9.3 Create Indonesian translations
  - Translate all English messages to Indonesian
  - Ensure all translation keys exist in both languages
  - _Requirements: 6.5_

- [ ] 9.4 Write tests for localization
  - Test translation keys exist in all languages
  - Test locale switching works correctly
  - Test API responses use correct language
  - _Requirements: 6.4, 6.5_

- [ ] 10. Implement API versioning
  - Set up v1 route group
  - Prepare for future v2 routes
  - _Requirements: 10.1, 10.2, 10.3_

- [ ] 10.1 Create API version structure
  - Create routes/api/v1.php
  - Move existing routes to v1 group
  - Add version prefix to all routes
  - _Requirements: 10.1_

- [ ] 10.2 Update API documentation
  - Document API versioning strategy
  - Add version to API documentation
  - Document deprecation policy
  - _Requirements: 10.3, 10.5_

- [ ] 11. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 12. Add performance monitoring
  - Configure slow query logging
  - Add request timing middleware
  - _Requirements: 12.1, 12.2, 12.3_

- [ ] 12.1 Configure database query logging
  - Enable slow query log in config/database.php
  - Set threshold for slow queries (e.g., 1000ms)
  - Log slow queries with context
  - _Requirements: 12.1_

- [ ] 12.2 Create performance monitoring middleware
  - Create RequestTimingMiddleware
  - Log request duration
  - Log memory usage
  - Add to global middleware stack
  - _Requirements: 12.2, 12.3_

- [ ] 12.3 Write tests for monitoring
  - Test slow queries are logged
  - Test request timing is recorded
  - _Requirements: 12.1, 12.2_

- [ ] 13. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
