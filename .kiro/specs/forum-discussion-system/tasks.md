# Implementation Plan - Forum/Discussion System

## 1. Setup Forum Module Structure

- [x] 1.1 Create Forums module using Laravel module generator
  - Run `php artisan module:make Forums`
  - Configure module.json with proper priority
  - _Requirements: All_

- [x] 1.2 Create database migrations
  - Create threads table migration
  - Create replies table migration
  - Create reactions table migration
  - Create forum_statistics table migration
  - _Requirements: 1.1, 2.1, 8.1, 10.1_

- [x] 1.3 Run migrations and verify database schema
  - Execute migrations
  - Verify foreign keys and indexes
  - _Requirements: All_

## 2. Implement Core Models

- [x] 2.1 Create Thread model with relationships
  - Define fillable fields and casts
  - Implement relationships (scheme, author, replies, reactions)
  - Add scopes (forScheme, pinned, resolved)
  - Implement helper methods (isPinned, isClosed, incrementViews)
  - _Requirements: 1.1, 1.3, 3.1, 4.4, 6.1_

- [ ] 2.2 Write property test for Thread model
  - **Property 1: Thread creation preserves data integrity**
  - **Validates: Requirements 1.1, 1.3**

- [x] 2.3 Create Reply model with nested structure
  - Define fillable fields and casts
  - Implement relationships (thread, author, parent, children, reactions)
  - Add depth calculation logic
  - Implement helper methods (isAcceptedAnswer, getDepth, canHaveChildren)
  - _Requirements: 2.1, 2.2, 2.3, 9.1_

- [ ] 2.4 Write property test for Reply nesting
  - **Property 4: Nested reply depth limit enforcement**
  - **Validates: Requirements 2.2**

- [x] 2.5 Create Reaction model with polymorphic relationship
  - Define fillable fields and casts
  - Implement morphTo relationship
  - Add toggle method for reaction management
  - _Requirements: 8.1, 8.2_

- [ ] 2.6 Write property test for Reaction toggle
  - **Property 19: Reaction toggle behavior**
  - **Validates: Requirements 8.2**

- [x] 2.7 Create ForumStatistic model
  - Define fillable fields and casts
  - Implement relationships
  - _Requirements: 10.1, 10.2, 10.3_

## 3. Implement Repository Layer

- [x] 3.1 Create ThreadRepository
  - Implement getThreadsForScheme with filters
  - Implement searchThreads with full-text search
  - Implement pagination logic
  - _Requirements: 3.1, 3.3, 3.4_

- [x] 3.2 Create ReplyRepository
  - Implement getRepliesForThread with hierarchy
  - Implement nested reply fetching
  - _Requirements: 4.1, 4.2_

- [x] 3.3 Create ForumStatisticsRepository
  - Implement statistics aggregation methods
  - Implement response rate calculation
  - _Requirements: 10.2, 10.4_

## 4. Implement Service Layer

- [x] 4.1 Create ForumService for thread operations
  - Implement createThread with validation
  - Implement updateThread
  - Implement deleteThread with soft delete
  - Implement getThreadsForScheme with sorting
  - Implement searchThreads
  - _Requirements: 1.1, 1.2, 3.1, 3.3, 5.1, 5.3_

- [ ] 4.2 Write property tests for ForumService
  - **Property 2: Authorization enforcement for thread creation**
  - **Property 5: Thread list sorting by activity**
  - **Property 7: Search matches query terms**
  - **Validates: Requirements 1.2, 1.4, 3.1, 3.3**

- [x] 4.3 Create ForumService for reply operations
  - Implement createReply with parent validation
  - Implement updateReply
  - Implement deleteReply with soft delete
  - _Requirements: 2.1, 2.2, 5.1, 5.3_

- [ ] 4.4 Write property test for reply creation
  - **Property 3: Reply creation preserves parent reference**
  - **Validates: Requirements 2.1, 2.3**

- [x] 4.5 Create ModerationService
  - Implement pinThread/unpinThread
  - Implement closeThread/openThread
  - Implement markAsAcceptedAnswer
  - Implement moderateDelete with logging
  - _Requirements: 6.1, 6.2, 6.3, 9.1, 9.4_

- [ ] 4.6 Write property tests for moderation
  - **Property 14: Thread closure prevents new replies**
  - **Property 21: Accepted answer exclusivity**
  - **Validates: Requirements 6.2, 9.4**

- [ ] 4.7 Create ForumNotificationService
  - Implement notifyThreadCreated
  - Implement notifyReplyCreated
  - Implement notifyThreadPinned
  - Implement notifyReactionAdded
  - Integrate with Notifications module
  - _Requirements: 1.5, 2.4, 7.1, 7.2, 7.3, 7.4_

- [ ] 4.8 Write property tests for notifications
  - **Property 16: Notification triggering for replies**
  - **Property 17: Broadcast notification for instructor threads**
  - **Validates: Requirements 7.1, 7.2, 7.3**

## 5. Implement Authorization

- [x] 5.1 Create ThreadPolicy
  - Implement view, create, update, delete policies
  - Implement pin, close policies for moderators
  - _Requirements: 1.2, 5.4, 6.5_

- [x] 5.2 Create ReplyPolicy
  - Implement view, create, update, delete policies
  - Implement markAsAccepted policy for instructors
  - _Requirements: 2.5, 5.4, 9.5_

- [x] 5.3 Create CheckForumAccess middleware
  - Verify user enrollment in scheme
  - _Requirements: 1.2, 2.5, 3.5_

- [ ] 5.4 Write property test for authorization
  - **Property 9: Thread access filtering by enrollment**
  - **Validates: Requirements 3.5**

## 6. Implement Controllers and API

- [x] 6.1 Create ThreadController
  - Implement index (list threads)
  - Implement store (create thread)
  - Implement show (thread detail)
  - Implement update
  - Implement destroy
  - _Requirements: 1.1, 3.1, 3.2, 4.1, 5.1_

- [x] 6.2 Create ReplyController
  - Implement store (create reply)
  - Implement update
  - Implement destroy
  - Implement accept (mark as accepted answer)
  - _Requirements: 2.1, 5.1, 9.1_

- [x] 6.3 Create ReactionController
  - Implement store (toggle reaction)
  - _Requirements: 8.1, 8.2_

- [x] 6.4 Create ForumStatisticsController
  - Implement index (get statistics)
  - _Requirements: 10.1, 10.2, 10.3, 10.4_

- [x] 6.5 Create Form Request validators
  - CreateThreadRequest
  - UpdateThreadRequest
  - CreateReplyRequest
  - UpdateReplyRequest
  - _Requirements: 1.1, 2.1_

- [x] 6.6 Define API routes
  - Define thread routes
  - Define reply routes
  - Define reaction routes
  - Define statistics routes
  - Apply middleware
  - _Requirements: All_

## 7. Implement Events and Listeners

- [x] 7.1 Create forum events
  - ThreadCreated event
  - ReplyCreated event
  - ThreadPinned event
  - ReactionAdded event
  - _Requirements: 1.5, 2.4, 7.3, 8.5_

- [x] 7.2 Create event listeners
  - NotifyInstructorOnThreadCreated
  - NotifyAuthorOnReply
  - NotifyUsersOnThreadPinned
  - NotifyAuthorOnReaction
  - UpdateForumStatistics
  - _Requirements: 1.5, 2.4, 7.1, 7.2, 7.3, 7.4, 8.5, 10.1_

## 8. Implement Statistics Tracking

- [x] 8.1 Create statistics update logic
  - Update statistics on thread creation
  - Update statistics on reply creation
  - Calculate response rate
  - Calculate average response time
  - _Requirements: 10.1, 10.2, 10.3, 10.4_

- [ ] 8.2 Write property tests for statistics
  - **Property 23: Statistics update on activity**
  - **Property 24: Response rate calculation**
  - **Validates: Requirements 10.1, 10.2, 10.3, 10.4**

## 9. Implement Search and Filtering

- [x] 9.1 Add full-text search indexes
  - Verify full-text indexes on threads table
  - _Requirements: 3.3_

- [x] 9.2 Implement search functionality
  - Implement searchThreads in ForumService
  - Add search query parsing
  - _Requirements: 3.3_

- [ ] 9.3 Write property test for search
  - **Property 7: Search matches query terms**
  - **Validates: Requirements 3.3**

- [x] 9.4 Implement filtering and pagination
  - Add filter by pinned, resolved, closed
  - Implement pagination with 20 items per page
  - _Requirements: 3.4, 3.5_

- [ ] 9.5 Write property test for pagination
  - **Property 8: Pagination page size limit**
  - **Validates: Requirements 3.4**

## 10. Testing and Quality Assurance

- [x] 10.1 Write integration tests
  - Test end-to-end thread creation flow
  - Test reply nesting with notifications
  - Test moderation actions
  - Test search and filtering
  - _Requirements: All_

- [x] 10.2 Test authorization and access control
  - Test enrollment-based access
  - Test moderator permissions
  - Test instructor permissions
  - _Requirements: 1.2, 2.5, 6.5, 9.5_

- [ ] 10.3 Test notification delivery
  - Test thread creation notifications
  - Test reply notifications
  - Test reaction notifications
  - _Requirements: 1.5, 2.4, 7.1, 7.2, 7.3, 7.4, 8.5_

## 11. Checkpoint - Ensure all tests pass
- Ensure all tests pass, ask the user if questions arise.

## 12. Documentation and Deployment

- [x] 12.1 Update API documentation
  - Document all endpoints
  - Add request/response examples
  - _Requirements: All_

- [x] 12.2 Create seeder for testing
  - Create sample threads
  - Create sample replies
  - Create sample reactions
  - _Requirements: All_

- [x] 12.3 Performance optimization
  - Add database indexes
  - Implement caching for thread lists
  - Optimize N+1 queries
  - _Requirements: All_

- [ ] 12.4 Deploy and monitor
  - Deploy to staging
  - Monitor performance
  - Fix any issues
  - _Requirements: All_
