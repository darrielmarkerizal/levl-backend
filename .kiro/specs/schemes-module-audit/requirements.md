# Requirements Document: Schemes Module Audit Fixes

## Introduction

Modul Schemes adalah modul inti untuk mengelola struktur pembelajaran (courses, units, lessons, lesson blocks) dalam sistem LMS Laravel. Hasil audit mendalam telah mengidentifikasi beberapa critical issues dan warnings yang perlu diperbaiki untuk meningkatkan data integrity, security, performance, dan consistency. Dokumen ini mendefinisikan requirements untuk memperbaiki temuan audit tersebut.

## Glossary

- **Course**: Entitas pembelajaran utama yang berisi units dan lessons
- **Unit**: Bagian dari course yang berisi lessons, memiliki order sequence
- **Lesson**: Konten pembelajaran dalam unit, memiliki order sequence
- **Lesson_Block**: Blok konten dalam lesson dengan media
- **Enrollment**: Relasi antara user dan course untuk akses pembelajaran
- **Progression_Service**: Service yang mengelola progress tracking user
- **Enrollment_Key**: Kunci akses untuk course dengan enrollment_type='key_based'
- **Course_Admin**: User dengan role Admin/Superadmin yang mengelola course tertentu
- **Published_Status**: Status course/unit/lesson yang sudah dipublikasikan
- **Draft_Status**: Status course/unit/lesson yang belum dipublikasikan
- **Sequential_Mode**: Mode progression dimana user harus menyelesaikan lesson berurutan
- **Free_Mode**: Mode progression dimana user bebas mengakses lesson apapun
- **Race_Condition**: Kondisi dimana multiple concurrent operations menyebabkan data inconsistency
- **Database_Locking**: Mekanisme untuk mencegah concurrent access ke data yang sama
- **Cache_Stampede**: Kondisi dimana multiple requests simultaneously hit database saat cache miss
- **Deadlock**: Kondisi dimana dua atau lebih transactions saling menunggu lock yang dipegang oleh yang lain

## Requirements

### Requirement 1: Enrollment Key Validation Consistency

**User Story:** As a system administrator, I want enrollment key validation to be consistent across all operations, so that courses with key_based enrollment cannot be published without a valid enrollment key.

#### Acceptance Criteria

1. WHEN a course with enrollment_type='key_based' is published, THE System SHALL validate that enrollment_key_hash is not null
2. WHEN enrollment_type is changed to 'key_based' and enrollment_key is empty, THE System SHALL auto-generate an enrollment key before allowing any status change
3. WHEN a course is published with enrollment_type='key_based', THE System SHALL ensure enrollment_key_hash exists and is valid
4. IF enrollment_type is changed from 'key_based' to another type, THEN THE System SHALL clear the enrollment_key_hash
5. WHEN updateEnrollmentSettings is called with enrollment_type='key_based', THE System SHALL validate or generate enrollment_key before saving

### Requirement 2: Race Condition Prevention for Course Publishing

**User Story:** As a system administrator, I want course publishing to be atomic and safe from race conditions, so that multiple administrators cannot publish the same course simultaneously causing data inconsistency.

#### Acceptance Criteria

1. WHEN a course publish operation starts, THE System SHALL acquire a database lock on the course record
2. WHILE a course is being published by one user, THE System SHALL prevent other users from publishing the same course
3. WHEN a publish operation completes or fails, THE System SHALL release the database lock
4. IF a publish operation encounters a lock timeout, THEN THE System SHALL return a user-friendly error message
5. WHEN concurrent publish attempts occur, THE System SHALL process them sequentially

### Requirement 3: Transaction Wrapping for Media Operations

**User Story:** As a content manager, I want media upload operations to be atomic, so that if an upload fails, the old media is not lost.

#### Acceptance Criteria

1. WHEN uploadThumbnail is called, THE System SHALL wrap clearMediaCollection and addMedia in a database transaction
2. WHEN uploadBanner is called, THE System SHALL wrap clearMediaCollection and addMedia in a database transaction
3. IF addMedia fails after clearMediaCollection, THEN THE System SHALL rollback the transaction and preserve the old media
4. WHEN a media upload transaction fails, THE System SHALL return a descriptive error message
5. WHEN a media upload succeeds, THE System SHALL commit the transaction and invalidate relevant caches

### Requirement 4: Race Condition Prevention for Unit Reordering

**User Story:** As a course instructor, I want unit reordering to be atomic and safe from race conditions, so that multiple users cannot reorder units simultaneously causing order corruption.

#### Acceptance Criteria

1. WHEN a unit reorder operation starts, THE System SHALL acquire database locks on all affected unit records
2. WHILE units are being reordered by one user, THE System SHALL prevent other users from reordering units in the same course
3. WHEN a reorder operation completes or fails, THE System SHALL release all database locks
4. IF a reorder operation encounters a lock timeout, THEN THE System SHALL return a user-friendly error message
5. WHEN concurrent reorder attempts occur, THE System SHALL process them sequentially

### Requirement 5: Order Gap Handling After Deletion

**User Story:** As a course instructor, I want unit and lesson order to be automatically normalized after deletion, so that there are no gaps in the order sequence.

#### Acceptance Criteria

1. WHEN a unit is deleted, THE System SHALL recalculate and normalize order for all remaining units in the course
2. WHEN a lesson is deleted, THE System SHALL recalculate and normalize order for all remaining lessons in the unit
3. AFTER order normalization, THE System SHALL ensure order values are sequential starting from 1
4. WHEN order normalization occurs, THE System SHALL wrap the operation in a database transaction
5. IF order normalization fails, THEN THE System SHALL rollback the deletion

### Requirement 6: Order Validation for Units and Lessons

**User Story:** As a system administrator, I want order values to be validated, so that duplicate or negative order values cannot exist.

#### Acceptance Criteria

1. WHEN a unit is created or updated, THE System SHALL validate that order is a positive integer
2. WHEN a unit is created or updated, THE System SHALL validate that order is unique within the course
3. WHEN a lesson is created or updated, THE System SHALL validate that order is a positive integer
4. WHEN a lesson is created or updated, THE System SHALL validate that order is unique within the unit
5. IF order validation fails, THEN THE System SHALL return a descriptive error message

### Requirement 7: Progression Access Control Consistency

**User Story:** As a student, I want progression access control to be consistent across all service layers, so that sequential mode is properly enforced regardless of which service method is called.

#### Acceptance Criteria

1. WHEN LessonService.getLessonForUser is called for a Student, THE System SHALL validate progression access using ProgressionService
2. WHEN LessonService.getLessonForUser is called for Admin or Instructor, THE System SHALL bypass progression validation
3. WHEN ProgressionService.validateLessonAccess is called, THE System SHALL apply the same access rules as LessonService
4. WHEN a Student attempts to access a lesson in sequential mode, THE System SHALL verify all previous lessons are completed
5. WHEN an Admin or Instructor attempts to access any lesson, THE System SHALL allow access regardless of progression mode

### Requirement 8: Lesson Content Validation

**User Story:** As a course instructor, I want lesson content to be validated based on content_type, so that lessons cannot be saved with missing or mismatched content.

#### Acceptance Criteria

1. WHEN a lesson is created or updated with content_type='video', THE System SHALL validate that content_url is not null
2. WHEN a lesson is created or updated with content_type='markdown', THE System SHALL validate that markdown_content is not null
3. WHEN a lesson is created or updated with content_type='external', THE System SHALL validate that content_url is not null
4. IF content validation fails, THEN THE System SHALL return a descriptive error message indicating which field is missing
5. WHEN content_type is changed, THE System SHALL validate that the corresponding content field is populated

### Requirement 9: Deadlock Prevention in Progression Service

**User Story:** As a student, I want lesson completion to be processed safely, so that concurrent completions do not cause deadlocks or data corruption.

#### Acceptance Criteria

1. WHEN markLessonCompleted is called, THE System SHALL acquire locks in a consistent order (Enrollment → LessonProgress → UnitProgress → CourseProgress)
2. WHEN multiple lessons are completed concurrently, THE System SHALL prevent deadlocks through lock ordering
3. IF a deadlock is detected, THEN THE System SHALL retry the operation with exponential backoff
4. WHEN a completion operation fails due to deadlock, THE System SHALL return a user-friendly error message
5. WHEN a completion operation succeeds, THE System SHALL dispatch events after transaction commit

### Requirement 10: Performance Optimization for Progress Data

**User Story:** As a student, I want my course progress to load quickly, so that I can view my progress without delays even for courses with many units and lessons.

#### Acceptance Criteria

1. WHEN getCourseProgressData is called, THE System SHALL use pagination for units and lessons
2. WHEN getCourseProgressData is called, THE System SHALL use lazy loading for lesson details
3. WHEN getCourseProgressData is called for a course with more than 50 lessons, THE System SHALL return results within 2 seconds
4. WHEN progress data is requested, THE System SHALL cache the results for 5 minutes
5. WHEN progress is updated, THE System SHALL invalidate the progress cache for that user and course

### Requirement 11: Course Admin View Policy

**User Story:** As a course admin, I want to view draft courses that I manage, so that I can review and edit them before publishing.

#### Acceptance Criteria

1. WHEN CoursePolicy.view is called for a course admin, THE System SHALL allow access to draft courses they manage
2. WHEN CoursePolicy.view is called for a course admin, THE System SHALL check the course_admins relationship
3. WHEN a course admin attempts to view a draft course they manage, THE System SHALL grant access
4. WHEN a course admin attempts to view a draft course they do not manage, THE System SHALL deny access
5. WHEN CoursePolicy.view is called for Superadmin, THE System SHALL allow access to all courses regardless of status

### Requirement 12: Enrollment Check in Course View Policy

**User Story:** As a student, I want course view policy to be consistent with unit and lesson policies, so that I cannot view course details without enrollment.

#### Acceptance Criteria

1. WHEN CoursePolicy.view is called for a Student, THE System SHALL check if the student is enrolled in the course
2. WHEN a Student attempts to view a published course without enrollment, THE System SHALL deny access to detailed course information
3. WHEN a Student attempts to view a published course with enrollment, THE System SHALL grant access
4. WHEN CoursePolicy.view is called for published courses, THE System SHALL allow unauthenticated users to view basic course information
5. WHEN CoursePolicy.view is called for draft courses, THE System SHALL deny access to unauthenticated users

### Requirement 13: Database Index Optimization

**User Story:** As a system administrator, I want database queries to be optimized with proper indexes, so that course listing and filtering operations are fast.

#### Acceptance Criteria

1. THE System SHALL create an index on courses.instructor_id for instructor course queries
2. THE System SHALL create an index on courses.category_id for category filtering queries
3. THE System SHALL create a composite index on (course_id, slug) in units table for unique slug per course
4. THE System SHALL create a composite index on (unit_id, slug) in lessons table for unique slug per unit
5. THE System SHALL create a composite index on (course_id, order) in units table for ordering queries

### Requirement 14: Unique Slug Constraints

**User Story:** As a course instructor, I want slugs to be unique within their parent scope, so that URLs are predictable and do not conflict globally.

#### Acceptance Criteria

1. THE System SHALL enforce unique constraint on (course_id, slug) in units table
2. THE System SHALL enforce unique constraint on (unit_id, slug) in lessons table
3. WHEN a unit slug conflicts within a course, THE System SHALL return a descriptive error message
4. WHEN a lesson slug conflicts within a unit, THE System SHALL return a descriptive error message
5. THE System SHALL allow the same slug to exist in different courses or units

### Requirement 15: Cache Stampede Prevention

**User Story:** As a system administrator, I want cache misses to be handled efficiently, so that multiple concurrent requests do not overwhelm the database.

#### Acceptance Criteria

1. WHEN getPublicCourses encounters a cache miss, THE System SHALL use cache locking to prevent stampede
2. WHILE one request is rebuilding the cache, THE System SHALL make other requests wait for the cache to be populated
3. WHEN cache locking times out, THE System SHALL allow the waiting request to query the database directly
4. WHEN cache is being rebuilt, THE System SHALL set a short-lived lock key to coordinate requests
5. WHEN cache rebuild completes, THE System SHALL release the lock and notify waiting requests

### Requirement 16: Complete Cache Invalidation

**User Story:** As a course instructor, I want all related caches to be invalidated when I update course content, so that students always see the latest information.

#### Acceptance Criteria

1. WHEN a unit is created, updated, or deleted, THE System SHALL invalidate the course cache
2. WHEN a unit is created, updated, or deleted, THE System SHALL invalidate the public courses listing cache
3. WHEN a lesson is created, updated, or deleted, THE System SHALL invalidate the course cache
4. WHEN a lesson is created, updated, or deleted, THE System SHALL invalidate the public courses listing cache
5. WHEN course enrollment settings are updated, THE System SHALL invalidate all related caches

### Requirement 17: Code Generation Error Handling

**User Story:** As a course instructor, I want course code generation to handle collisions gracefully, so that I receive a clear error message if code generation fails.

#### Acceptance Criteria

1. WHEN generateCourseCode encounters a collision, THE System SHALL retry up to 3 times with different codes
2. IF all retry attempts fail, THEN THE System SHALL return a user-friendly error message
3. WHEN code generation succeeds, THE System SHALL validate the code is unique before returning
4. WHEN code generation is called, THE System SHALL use a cryptographically secure random generator
5. THE System SHALL log code generation failures for monitoring purposes

### Requirement 18: Weighted Progress Calculation

**User Story:** As a student, I want my course progress to reflect the actual amount of content completed, so that units with more lessons contribute more to overall progress.

#### Acceptance Criteria

1. WHEN calculating course progress, THE System SHALL weight unit progress by the number of lessons in each unit
2. WHEN calculating unit progress, THE System SHALL calculate as (completed lessons / total lessons) * 100
3. WHEN calculating course progress, THE System SHALL calculate as (sum of weighted unit progress) / (total lessons in course) * 100
4. WHEN a unit has no lessons, THE System SHALL exclude it from progress calculation
5. WHEN progress is calculated, THE System SHALL round the result to 2 decimal places

### Requirement 19: Atomic Media Operations

**User Story:** As a content manager, I want media deletion to be safe, so that media is not deleted if it's still referenced by other records.

#### Acceptance Criteria

1. WHEN deleteThumbnail is called, THE System SHALL verify the course exists before deleting media
2. WHEN deleteBanner is called, THE System SHALL verify the course exists before deleting media
3. WHEN media deletion occurs, THE System SHALL invalidate the course cache
4. WHEN media deletion fails, THE System SHALL return a descriptive error message
5. WHEN media deletion succeeds, THE System SHALL log the operation for audit purposes

### Requirement 20: Progression Mode Validation

**User Story:** As a course instructor, I want progression mode to be validated when set, so that invalid progression modes cannot be saved.

#### Acceptance Criteria

1. WHEN a course is created or updated with progression_mode, THE System SHALL validate it is either 'sequential' or 'free'
2. IF progression_mode is invalid, THEN THE System SHALL return a descriptive error message
3. WHEN progression_mode is not provided, THE System SHALL default to 'sequential'
4. WHEN progression_mode is changed from 'sequential' to 'free', THE System SHALL allow all enrolled students to access all lessons
5. WHEN progression_mode is changed from 'free' to 'sequential', THE System SHALL enforce sequential access for future lesson access
