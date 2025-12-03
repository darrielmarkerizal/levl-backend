# Implementation Plan - Info & News Management System

## 1. Setup Content Module Structure

- [x] 1.1 Create Content module using Laravel module generator
  - Run `php artisan module:make Content`
  - Configure module.json with proper priority
  - _Requirements: All_

- [x] 1.2 Create database migrations
  - Create announcements table migration
  - Create news table migration
  - Create content_reads table migration
  - Create content_revisions table migration
  - Create content_categories table migration
  - _Requirements: 1.1, 3.1, 5.2, 6.2_

- [x] 1.3 Run migrations and verify database schema
  - Execute migrations
  - Verify foreign keys and indexes
  - _Requirements: All_

## 2. Implement Core Models

- [ ] 2.1 Create Announcement model
  - Define fillable fields and casts
  - Implement relationships (author, course, reads, revisions)
  - Add scopes (published, forUser, forCourse)
  - Implement helper methods (isPublished, isScheduled, markAsReadBy, isReadBy)
  - _Requirements: 1.1, 1.3, 1.4, 2.1, 2.2, 5.2_

- [ ] 2.2 Write property test for Announcement creation
  - **Property 1: Announcement creation data integrity**
  - **Validates: Requirements 1.1, 1.3**

- [x] 2.3 Create News model
  - Define fillable fields and casts
  - Implement relationships (author, categories, tags, reads, revisions)
  - Add scopes (published, featured)
  - Implement helper methods (incrementViews, getTrendingScore)
  - _Requirements: 3.1, 3.2, 3.3, 3.5, 5.4_

- [ ] 2.4 Write property test for News creation
  - **Property 5: News creation data completeness**
  - **Validates: Requirements 3.1, 3.5**

- [x] 2.5 Create ContentRead model with polymorphic relationship
  - Define fillable fields
  - Implement morphTo relationship
  - Add unique constraint for user-content pair
  - _Requirements: 5.2, 5.5_

- [ ] 2.6 Write property test for read tracking
  - **Property 12: Read tracking on view**
  - **Validates: Requirements 5.2, 5.5**

- [x] 2.7 Create ContentRevision model
  - Define fillable fields and casts
  - Implement relationships
  - _Requirements: 6.2_

- [x] 2.8 Create ContentCategory model
  - Define fillable fields
  - Implement relationships
  - _Requirements: 3.2_

## 3. Implement Repository Layer

- [x] 3.1 Create AnnouncementRepository
  - Implement getAnnouncementsForUser with targeting logic
  - Implement filtering by course, status
  - Implement pagination
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 4.3_

- [x] 3.2 Create NewsRepository
  - Implement getNewsFeed with sorting
  - Implement filtering by category, date range
  - Implement pagination
  - _Requirements: 4.1, 4.2, 8.2_

- [x] 3.3 Create ContentStatisticsRepository
  - Implement view count aggregation
  - Implement read rate calculation
  - Implement trending calculation
  - _Requirements: 7.1, 7.2, 7.4_

## 4. Implement Service Layer

- [x] 4.1 Create ContentService for announcement operations
  - Implement createAnnouncement with validation
  - Implement updateContent
  - Implement publishContent with timestamp
  - Implement deleteContent with soft delete
  - Implement getAnnouncementsForUser
  - _Requirements: 1.1, 1.3, 1.4, 1.5, 4.3, 6.1, 6.3_

- [ ] 4.2 Write property tests for ContentService
  - **Property 2: Empty content validation**
  - **Property 3: Publication timestamp tracking**
  - **Validates: Requirements 1.4, 1.5**

- [x] 4.3 Create ContentService for news operations
  - Implement createNews with validation
  - Implement updateContent
  - Implement publishContent
  - Implement deleteContent
  - Implement getNewsFeed
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 4.1, 6.1, 6.3_

- [ ] 4.4 Write property test for category associations
  - **Property 6: Category and tag associations**
  - **Validates: Requirements 3.2, 3.3**

- [x] 4.5 Create ContentNotificationService
  - Implement notifyTargetAudience with targeting logic
  - Implement getTargetUsers (all, role, course)
  - Implement notifyNewNews
  - Integrate with Notifications module
  - _Requirements: 2.3, 2.4, 2.5_

- [ ] 4.6 Write property test for notification targeting
  - **Property 4: Target audience notification accuracy**
  - **Validates: Requirements 2.3, 2.4, 2.5**

- [x] 4.7 Create ContentSchedulingService
  - Implement schedulePublication
  - Implement cancelSchedule
  - Implement publishScheduledContent (for scheduler)
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 4.8 Write property tests for scheduling
  - **Property 22: Scheduled publication automation**
  - **Property 23: Schedule cancellation**
  - **Validates: Requirements 9.2, 9.4**

- [x] 4.9 Create ContentStatisticsService
  - Implement getStatistics
  - Implement calculateReadRate
  - Implement getUnreadUsers
  - Implement getTrendingNews
  - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [ ] 4.10 Write property test for statistics
  - **Property 17: Read rate calculation**
  - **Validates: Requirements 7.2**

## 5. Implement Authorization

- [x] 5.1 Create ContentPolicy
  - Implement view, create, update, delete policies
  - Implement publish policy for admin/instructor
  - _Requirements: 1.1, 3.1, 6.1, 10.4_

- [x] 5.2 Implement course-specific authorization
  - Verify instructor has access to course for course announcements
  - _Requirements: 10.4_

- [ ] 5.3 Write property test for authorization
  - **Property 25: Instructor authorization**
  - **Validates: Requirements 10.4**

## 6. Implement Controllers and API

- [x] 6.1 Create AnnouncementController
  - Implement index (list announcements)
  - Implement store (create announcement)
  - Implement show (announcement detail)
  - Implement update
  - Implement destroy
  - Implement publish
  - Implement schedule
  - Implement markAsRead
  - _Requirements: 1.1, 4.3, 5.1, 5.2, 6.1, 9.1_

- [x] 6.2 Create NewsController
  - Implement index (news feed)
  - Implement store (create news)
  - Implement show (news detail)
  - Implement update
  - Implement destroy
  - Implement publish
  - Implement trending
  - _Requirements: 3.1, 4.1, 4.2, 5.1, 6.1, 7.4_

- [x] 6.3 Create ContentStatisticsController
  - Implement index (get statistics)
  - Implement show (content-specific statistics)
  - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [x] 6.4 Create Form Request validators
  - CreateAnnouncementRequest
  - CreateNewsRequest
  - UpdateContentRequest
  - ScheduleContentRequest
  - _Requirements: 1.1, 1.5, 3.1, 9.1_

- [x] 6.5 Define API routes
  - Define announcement routes
  - Define news routes
  - Define course announcement routes
  - Define statistics routes
  - Define search routes
  - Apply middleware
  - _Requirements: All_

## 7. Implement Events and Listeners

- [x] 7.1 Create content events
  - AnnouncementPublished event
  - NewsPublished event
  - ContentScheduled event
  - _Requirements: 1.5, 2.3, 9.5_

- [x] 7.2 Create event listeners
  - NotifyTargetAudienceOnAnnouncementPublished
  - NotifyUsersOnNewsPublished
  - NotifyUsersOnScheduledPublication
  - _Requirements: 1.5, 2.3, 9.5_

## 8. Implement Scheduling Job

- [x] 8.1 Create PublishScheduledContent job
  - Query content with scheduled_at <= now
  - Publish content and trigger notifications
  - Log published content
  - _Requirements: 9.2, 9.5_

- [x] 8.2 Register job in scheduler
  - Add to Kernel schedule (run every minute or 5 minutes)
  - _Requirements: 9.2_

- [ ] 8.3 Write property test for scheduled publishing
  - **Property 22: Scheduled publication automation**
  - **Validates: Requirements 9.2**

## 9. Implement Search and Filtering

- [x] 9.1 Add full-text search indexes
  - Verify full-text indexes on announcements and news tables
  - _Requirements: 8.1_

- [x] 9.2 Implement search functionality
  - Implement searchContent in ContentService
  - Add search query parsing
  - Support category and date range filters
  - _Requirements: 8.1, 8.2, 8.3_

- [ ] 9.3 Write property tests for search
  - **Property 20: Search result matching**
  - **Property 21: Filter application**
  - **Validates: Requirements 8.1, 8.2**

- [x] 9.4 Implement read/unread filtering
  - Add filter for read/unread status
  - _Requirements: 8.4_

## 10. Implement Revision History

- [x] 10.1 Create revision tracking logic
  - Save revision on content update
  - Store previous content and editor info
  - _Requirements: 6.2_

- [ ] 10.2 Write property test for revisions
  - **Property 14: Edit revision history**
  - **Validates: Requirements 6.2**

## 11. Implement Course-Specific Announcements

- [x] 11.1 Add course announcement routes
  - GET /api/courses/{course}/announcements
  - POST /api/courses/{course}/announcements
  - _Requirements: 10.1, 10.2, 10.5_

- [x] 11.2 Implement course announcement logic
  - Associate announcement with course
  - Filter announcements by course
  - Notify enrolled students
  - _Requirements: 10.1, 10.2, 10.3, 10.5_

- [ ] 11.3 Write property test for course announcements
  - **Property 24: Course announcement association**
  - **Validates: Requirements 10.1**

## 12. Testing and Quality Assurance

- [x] 12.1 Write integration tests
  - Test end-to-end announcement creation and notification
  - Test news publication flow
  - Test scheduled publishing
  - Test search and filtering
  - _Requirements: All_

- [x] 12.2 Test authorization and access control
  - Test admin/instructor permissions
  - Test course-specific authorization
  - _Requirements: 1.1, 3.1, 10.4_

- [x] 12.3 Test notification delivery
  - Test target audience filtering
  - Test course-based notifications
  - Test role-based notifications
  - _Requirements: 2.3, 2.4, 2.5, 10.2_

- [x] 12.4 Test read tracking
  - Test read status updates
  - Test unread indicators
  - Test read rate calculation
  - _Requirements: 5.2, 5.5, 7.2_

## 13. Checkpoint - Ensure all tests pass
- [x] Ensure all tests pass, ask the user if questions arise.

## 14. Documentation and Deployment

- [x] 14.1 Update API documentation
  - Document all endpoints
  - Add request/response examples
  - _Requirements: All_

- [x] 14.2 Create seeder for testing
  - Create sample announcements
  - Create sample news
  - Create sample categories
  - _Requirements: All_

- [x] 14.3 Performance optimization
  - Add database indexes
  - Implement caching for news feed
  - Optimize N+1 queries
  - _Requirements: All_

- [ ] 14.4 Deploy and monitor
  - Deploy to staging
  - Test scheduled job execution
  - Monitor performance
  - Fix any issues
  - _Requirements: All_
