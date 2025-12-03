# Implementation Plan - Missing Features Implementation

- [x] 1. Set up Search module infrastructure
  - Create Search module structure
  - Install and configure Laravel Scout and Meilisearch
  - Set up search index configuration
  - _Requirements: 1.1, 1.2_

- [x] 1.1 Create Search module
  - Generate Search module using nwidart/laravel-modules
  - Create module structure (Models, Controllers, Services, Repositories)
  - Register module in modules_statuses.json
  - _Requirements: 1.1_

- [x] 1.2 Install Laravel Scout and Meilisearch
  - Install laravel/scout package
  - Install meilisearch/meilisearch-php
  - Configure scout.php with Meilisearch settings
  - Start Meilisearch server
  - _Requirements: 1.1_

- [x] 1.3 Configure Course model for search
  - Add Searchable trait to Course model
  - Implement toSearchableArray() method
  - Define searchableAs() index name
  - Index existing courses
  - _Requirements: 1.1, 1.2_

- [x] 1.4 Write tests for search indexing
  - Test Course model is searchable
  - Test toSearchableArray() returns correct data
  - Test courses are indexed correctly
  - _Requirements: 1.1_

- [x] 2. Implement advanced search functionality
  - Create search service with filtering
  - Implement autocomplete
  - Add search history tracking
  - _Requirements: 1.1, 1.3, 1.4, 1.5, 1.6_

- [x] 2.1 Create SearchService
  - Implement SearchServiceInterface
  - Create search() method with query and filters
  - Implement filter builder for categories, levels, instructors
  - Add sorting options (relevance, date, popularity)
  - _Requirements: 1.1, 1.3, 1.4, 1.5_

- [x] 2.2 Create SearchFilterBuilder
  - Implement addCategoryFilter()
  - Implement addLevelFilter()
  - Implement addInstructorFilter()
  - Implement addDurationFilter()
  - Implement addStatusFilter()
  - _Requirements: 1.3, 1.4_

- [x] 2.3 Implement autocomplete suggestions
  - Create getSuggestions() method in SearchService
  - Query Meilisearch for partial matches
  - Return top 10 suggestions
  - _Requirements: 1.6_

- [x] 2.4 Create SearchHistory model and tracking
  - Create search_history migration
  - Create SearchHistory model
  - Implement saveSearchHistory() in SearchService
  - Track query, filters, results_count, clicked_result
  - _Requirements: 1.8, 6.1_

- [x] 2.5 Write unit tests for SearchService
  - Test search with filters returns correct results
  - Test autocomplete returns suggestions
  - Test search history is saved
  - _Requirements: 1.3, 1.6, 1.8_

- [x] 3. Create search API endpoints
  - Implement search controller
  - Add routes for search, autocomplete, history
  - _Requirements: 1.1, 1.6, 1.7, 1.8_

- [x] 3.1 Create SearchController
  - Implement search() method with query and filters
  - Implement autocomplete() method
  - Implement getSearchHistory() method
  - Implement clearSearchHistory() method
  - Return paginated results with metadata
  - _Requirements: 1.1, 1.6, 1.8, 1.9_

- [x] 3.2 Add search routes
  - Add GET /api/v1/search/courses route
  - Add GET /api/v1/search/autocomplete route
  - Add GET /api/v1/search/history route
  - Add DELETE /api/v1/search/history route
  - _Requirements: 1.1, 1.6, 1.8_

- [x] 3.3 Write API tests for search endpoints
  - Test search endpoint returns filtered results
  - Test autocomplete returns suggestions
  - Test search history is saved and retrieved
  - Test no results returns suggestions
  - _Requirements: 1.1, 1.6, 1.7, 1.8_

- [x] 4. Set up Assessment Registration module
  - Create assessment registration model and migration
  - Implement prerequisite checking
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 4.1 Create AssessmentRegistration model
  - Create assessment_registrations migration
  - Define fillable fields and casts
  - Add status and payment_status constants
  - Define relationships (user, assessment, enrollment)
  - _Requirements: 2.1, 2.8_

- [x] 4.2 Create AssessmentRegistrationService
  - Implement AssessmentRegistrationServiceInterface
  - Create register() method with prerequisite check
  - Implement checkPrerequisites() method
  - Throw PrerequisitesNotMetException if not met
  - _Requirements: 2.1, 2.2_

- [x] 4.3 Implement prerequisite checking logic
  - Check if user completed required courses
  - Check if user has required enrollments
  - Return PrerequisiteCheckResult DTO
  - _Requirements: 2.2_

- [x] 4.4 Write unit tests for prerequisite checking
  - Test prerequisites are checked correctly
  - Test exception is thrown when not met
  - Test registration succeeds when prerequisites met
  - _Requirements: 2.2_

- [x] 5. Implement assessment scheduling
  - Create slot availability system
  - Implement registration with scheduling
  - Add capacity management
  - _Requirements: 2.3, 2.4, 2.5_

- [x] 5.1 Create assessment time slots system
  - Create assessment_time_slots migration (optional)
  - Implement getAvailableSlots() method
  - Calculate available capacity per slot
  - Return slots with availability status
  - _Requirements: 2.3, 2.4_

- [x] 5.2 Implement slot booking
  - Update register() to accept scheduled_at
  - Check slot availability before booking
  - Throw AssessmentFullException if slot full
  - Decrement available capacity
  - _Requirements: 2.3, 2.4_

- [x] 5.3 Add confirmation notifications
  - Fire AssessmentRegistered event
  - Send confirmation email with details
  - Update confirmation_sent_at timestamp
  - _Requirements: 2.5_

- [x] 5.4 Write tests for scheduling
  - Test available slots are returned correctly
  - Test slot capacity is enforced
  - Test confirmation notification is sent
  - _Requirements: 2.3, 2.4, 2.5_

- [x] 6. Implement assessment registration management
  - Add cancellation functionality
  - Create status tracking
  - Add reminder notifications
  - _Requirements: 2.7, 2.8, 2.9_

- [x] 6.1 Implement registration cancellation
  - Create cancel() method in service
  - Check cancellation timeframe
  - Throw CancellationNotAllowedException if too late
  - Update status to cancelled
  - _Requirements: 2.9_

- [x] 6.2 Add reminder notification system
  - Create scheduled job for sending reminders
  - Send reminder 24 hours before assessment
  - Update reminder_sent_at timestamp
  - _Requirements: 2.7_

- [x] 6.3 Create assessment registration API endpoints
  - Create AssessmentRegistrationController
  - Add POST /api/v1/assessments/{id}/register route
  - Add GET /api/v1/assessments/{id}/prerequisites route
  - Add GET /api/v1/assessments/{id}/slots route
  - Add DELETE /api/v1/assessment-registrations/{id} route
  - _Requirements: 2.1, 2.2, 2.3, 2.9_

- [x] 6.4 Write integration tests for registration flow
  - Test complete registration flow
  - Test cancellation within timeframe
  - Test cancellation fails after deadline
  - Test reminder notifications are sent
  - _Requirements: 2.1, 2.7, 2.9_

- [ ] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Implement content workflow system
  - Create workflow state machine
  - Add workflow history tracking
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 8.1 Create ContentWorkflowService
  - Define workflow states (draft, submitted, in_review, approved, rejected, scheduled, published, archived)
  - Define allowed transitions
  - Implement transition() method with validation
  - Throw InvalidTransitionException for invalid transitions
  - _Requirements: 3.2, 3.3, 3.4_

- [x] 8.2 Create ContentWorkflowHistory model
  - Create content_workflow_history migration
  - Track from_state, to_state, user_id, note
  - Define relationships
  - _Requirements: 3.2_

- [x] 8.3 Implement workflow transition methods
  - Create submitForReview() method
  - Create approve() method
  - Create reject() method with reason
  - Create schedule() method with publish date
  - Create publish() method
  - _Requirements: 3.2, 3.3, 3.4, 3.5_

- [x] 8.4 Write unit tests for workflow
  - Test valid transitions succeed
  - Test invalid transitions throw exception
  - Test workflow history is recorded
  - _Requirements: 3.2, 3.3, 3.4_

- [x] 9. Add content approval interface
  - Create approval endpoints
  - Add reviewer notifications
  - _Requirements: 3.3, 3.4, 3.6_

- [x] 9.1 Create ContentApprovalController
  - Add POST /api/v1/content/{type}/{id}/submit route
  - Add POST /api/v1/content/{type}/{id}/approve route
  - Add POST /api/v1/content/{type}/{id}/reject route
  - Add GET /api/v1/content/pending-review route
  - _Requirements: 3.2, 3.3, 3.4_

- [x] 9.2 Add reviewer notifications
  - Fire ContentSubmitted event
  - Notify reviewers when content submitted
  - Fire ContentApproved/ContentRejected events
  - Notify author of approval/rejection
  - _Requirements: 3.3, 3.6_

- [x] 9.3 Write API tests for approval workflow
  - Test submit for review works
  - Test approve/reject works
  - Test notifications are sent
  - _Requirements: 3.3, 3.4, 3.6_

- [ ] 10. Implement bulk content operations
  - Add bulk upload functionality
  - Add bulk publishing
  - _Requirements: 3.10_

- [ ] 10.1 Create bulk upload endpoint
  - Add POST /api/v1/content/bulk-upload route
  - Accept multiple files or CSV
  - Validate and create content in batch
  - Return success/failure report
  - _Requirements: 3.10_

- [ ] 10.2 Create bulk publish endpoint
  - Add POST /api/v1/content/bulk-publish route
  - Accept array of content IDs
  - Publish all in transaction
  - Return success/failure report
  - _Requirements: 3.10_

- [ ] 10.3 Write tests for bulk operations
  - Test bulk upload creates multiple content
  - Test bulk publish publishes all content
  - Test partial failures are handled
  - _Requirements: 3.10_

- [ ] 11. Set up File Management module
  - Create Files module structure
  - Create file library models
  - _Requirements: 4.1, 4.2_

- [ ] 11.1 Create Files module
  - Generate Files module
  - Create module structure
  - Register module
  - _Requirements: 4.1_

- [ ] 11.2 Create FileLibrary and FileFolder models
  - Create file_folders migration
  - Create file_library migration
  - Define models with relationships
  - Add visibility and sharing fields
  - _Requirements: 4.1, 4.2, 4.3, 4.5_

- [ ] 11.3 Create FileManagementService
  - Implement FileManagementServiceInterface
  - Create upload() method with quota check
  - Implement folder creation and management
  - _Requirements: 4.1, 4.2, 4.7_

- [ ] 11.4 Write unit tests for file models
  - Test file-folder relationships
  - Test file versioning relationships
  - _Requirements: 4.2, 4.6_

- [ ] 12. Implement file versioning and quota
  - Add version control for files
  - Implement storage quota checking
  - _Requirements: 4.6, 4.7_

- [ ] 12.1 Implement file versioning
  - Create createVersion() method
  - Link new version to parent file
  - Increment version number
  - Maintain version history
  - _Requirements: 4.6_

- [ ] 12.2 Implement storage quota system
  - Add storage_quota field to users table
  - Create checkQuota() method
  - Calculate current usage
  - Throw StorageQuotaExceededException if exceeded
  - _Requirements: 4.7_

- [ ] 12.3 Add file sharing functionality
  - Implement shareWith() method
  - Update shared_with array
  - Check permissions on file access
  - _Requirements: 4.5_

- [ ] 12.4 Write tests for versioning and quota
  - Test file versions are created correctly
  - Test quota is enforced
  - Test file sharing permissions work
  - _Requirements: 4.5, 4.6, 4.7_

- [ ] 13. Create file management API endpoints
  - Implement file library controller
  - Add routes for upload, download, share
  - _Requirements: 4.1, 4.4, 4.5, 4.8, 4.9_

- [ ] 13.1 Create FileLibraryController
  - Add POST /api/v1/files/upload route
  - Add GET /api/v1/files route (list files)
  - Add GET /api/v1/files/{id} route (get file)
  - Add DELETE /api/v1/files/{id} route
  - Add POST /api/v1/files/{id}/share route
  - Add POST /api/v1/files/{id}/version route
  - Add POST /api/v1/files/move route (batch move)
  - _Requirements: 4.1, 4.5, 4.6, 4.9_

- [ ] 13.2 Add file preview functionality
  - Implement preview endpoint for images
  - Add preview for PDFs
  - Return appropriate response for other types
  - _Requirements: 4.8_

- [ ] 13.3 Create folder management endpoints
  - Add POST /api/v1/folders route (create folder)
  - Add GET /api/v1/folders route (list folders)
  - Add DELETE /api/v1/folders/{id} route
  - _Requirements: 4.2_

- [ ] 13.4 Write API tests for file management
  - Test file upload works
  - Test quota is enforced on upload
  - Test file sharing works
  - Test file versioning works
  - Test batch operations work
  - _Requirements: 4.1, 4.5, 4.6, 4.7, 4.9_

- [ ] 14. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 15. Implement recommendation system
  - Create recommendation service
  - Implement collaborative filtering
  - Add trending algorithm
  - _Requirements: 6.2, 6.4, 6.5, 6.6, 6.7_

- [ ] 15.1 Create RecommendationService
  - Implement RecommendationServiceInterface
  - Create getRecommendations() method
  - Track user interactions (views, enrollments, completions)
  - _Requirements: 6.2, 6.4_

- [ ] 15.2 Implement content-based filtering
  - Create getSimilarCourses() method
  - Compare course attributes (category, tags, level)
  - Calculate similarity scores
  - Return top similar courses
  - _Requirements: 6.5_

- [ ] 15.3 Implement collaborative filtering
  - Find users with similar interests
  - Recommend courses liked by similar users
  - Weight recommendations by similarity
  - _Requirements: 6.6_

- [ ] 15.4 Implement trending algorithm
  - Create getTrendingCourses() method
  - Calculate trend score based on recent activity
  - Support day/week/month periods
  - Cache trending results
  - _Requirements: 6.10_

- [ ] 15.5 Write tests for recommendations
  - Test similar courses are returned
  - Test recommendations are personalized
  - Test trending courses are calculated correctly
  - _Requirements: 6.5, 6.6, 6.10_

- [ ] 16. Create recommendation API endpoints
  - Add endpoints for recommendations
  - Display recommendations with explanations
  - _Requirements: 6.2, 6.7, 6.9_

- [ ] 16.1 Create RecommendationController
  - Add GET /api/v1/recommendations route
  - Add GET /api/v1/courses/{id}/similar route
  - Add GET /api/v1/courses/trending route
  - Include explanation for each recommendation
  - _Requirements: 6.2, 6.7, 6.9_

- [ ] 16.2 Write API tests for recommendations
  - Test recommendations endpoint returns results
  - Test similar courses endpoint works
  - Test trending courses endpoint works
  - _Requirements: 6.2, 6.9, 6.10_

- [x] 17. Implement notification preferences
  - Create notification preference model
  - Add preference management
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 17.1 Create NotificationPreference model
  - Create notification_preferences migration
  - Define categories (course_updates, assignments, assessments, forum, achievements, system)
  - Define channels (email, in_app, push)
  - Define frequency (immediate, daily, weekly)
  - _Requirements: 6.2, 6.3, 6.4_

- [x] 17.2 Create NotificationPreferenceService
  - Implement getPreferences() method
  - Implement updatePreferences() method
  - Implement shouldSendNotification() check
  - _Requirements: 6.2, 6.7_

- [x] 17.3 Update notification system to respect preferences
  - Check preferences before sending notifications
  - Filter by enabled categories and channels
  - Respect frequency settings
  - Always send critical notifications
  - _Requirements: 6.2, 6.3, 6.6_

- [x] 17.4 Write tests for notification preferences
  - Test preferences are saved correctly
  - Test notifications respect preferences
  - Test critical notifications always sent
  - _Requirements: 6.2, 6.3, 6.6_

- [x] 18. Create notification preference API endpoints
  - Add endpoints for managing preferences
  - _Requirements: 6.1, 6.2, 6.7_

- [x] 18.1 Create NotificationPreferenceController
  - Add GET /api/v1/notification-preferences route
  - Add PUT /api/v1/notification-preferences route
  - Add POST /api/v1/notification-preferences/reset route
  - _Requirements: 6.1, 6.2, 6.7_

- [x] 18.2 Write API tests for preferences
  - Test get preferences returns current settings
  - Test update preferences works
  - Test reset to defaults works
  - _Requirements: 6.1, 6.2, 6.7_

- [ ] 19. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
