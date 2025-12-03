# Design Document - Info & News Management System

## Overview

Info & News Management System adalah modul Laravel yang memungkinkan admin dan instruktur untuk membuat, mengelola, dan mempublikasikan informasi, pengumuman, dan berita kepada pengguna platform. Sistem ini mendukung targeting audience, scheduling, read tracking, dan statistik untuk komunikasi yang efektif.

## Architecture

### Module Structure

```
Modules/Content/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AnnouncementController.php
│   │   │   ├── NewsController.php
│   │   │   └── ContentStatisticsController.php
│   │   └── Requests/
│   │       ├── CreateAnnouncementRequest.php
│   │       ├── CreateNewsRequest.php
│   │       └── UpdateContentRequest.php
│   ├── Models/
│   │   ├── Announcement.php
│   │   ├── News.php
│   │   ├── ContentRead.php
│   │   ├── ContentRevision.php
│   │   └── ContentCategory.php
│   ├── Repositories/
│   │   ├── AnnouncementRepository.php
│   │   ├── NewsRepository.php
│   │   └── ContentStatisticsRepository.php
│   ├── Services/
│   │   ├── ContentService.php
│   │   ├── ContentNotificationService.php
│   │   ├── ContentSchedulingService.php
│   │   └── ContentStatisticsService.php
│   ├── Jobs/
│   │   └── PublishScheduledContent.php
│   └── Events/
│       ├── AnnouncementPublished.php
│       └── NewsPublished.php
└── database/
    └── migrations/
        ├── create_announcements_table.php
        ├── create_news_table.php
        ├── create_content_reads_table.php
        ├── create_content_revisions_table.php
        └── create_content_categories_table.php
```

## Components and Interfaces

### Key Models

**Announcement Model:**
```php
class Announcement extends Model
{
    // Relationships
    public function author(): BelongsTo;
    public function course(): BelongsTo; // nullable for global announcements
    public function reads(): HasMany;
    public function revisions(): HasMany;
    
    // Scopes
    public function scopePublished($query);
    public function scopeForUser($query, User $user);
    public function scopeForCourse($query, $courseId);
    
    // Methods
    public function isPublished(): bool;
    public function isScheduled(): bool;
    public function markAsReadBy(User $user): void;
    public function isReadBy(User $user): bool;
}
```

**News Model:**
```php
class News extends Model
{
    // Relationships
    public function author(): BelongsTo;
    public function categories(): BelongsToMany;
    public function tags(): BelongsToMany;
    public function reads(): HasMany;
    public function revisions(): HasMany;
    
    // Scopes
    public function scopePublished($query);
    public function scopeFeatured($query);
    
    // Methods
    public function incrementViews(): void;
    public function getTrendingScore(): float;
}
```

### Key Services

**ContentService:**
```php
class ContentService
{
    public function createAnnouncement(array $data, User $author): Announcement;
    public function createNews(array $data, User $author): News;
    public function updateContent(Model $content, array $data): Model;
    public function publishContent(Model $content): bool;
    public function scheduleContent(Model $content, Carbon $publishAt): bool;
    public function deleteContent(Model $content, User $user): bool;
    public function getAnnouncementsForUser(User $user, array $filters = []): Collection;
    public function getNewsFeed(array $filters = []): Collection;
    public function searchContent(string $query, array $filters = []): Collection;
}
```

**ContentNotificationService:**
```php
class ContentNotificationService
{
    public function notifyTargetAudience(Announcement $announcement): void;
    public function getTargetUsers(Announcement $announcement): Collection;
    public function notifyNewNews(News $news): void;
}
```

**ContentSchedulingService:**
```php
class ContentSchedulingService
{
    public function schedulePublication(Model $content, Carbon $publishAt): bool;
    public function cancelSchedule(Model $content): bool;
    public function publishScheduledContent(): int;
}
```

## Data Models

### Database Schema

#### announcements table
```sql
CREATE TABLE announcements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    author_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('draft', 'published', 'scheduled') DEFAULT 'draft',
    target_type ENUM('all', 'role', 'course') DEFAULT 'all',
    target_value VARCHAR(255) NULL,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    published_at TIMESTAMP NULL,
    scheduled_at TIMESTAMP NULL,
    views_count INT UNSIGNED DEFAULT 0,
    deleted_at TIMESTAMP NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status_published (status, published_at),
    INDEX idx_course (course_id, published_at),
    FULLTEXT INDEX idx_search (title, content)
);
```

#### news table
```sql
CREATE TABLE news (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT NULL,
    content TEXT NOT NULL,
    featured_image_path VARCHAR(255) NULL,
    status ENUM('draft', 'published', 'scheduled') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    scheduled_at TIMESTAMP NULL,
    views_count INT UNSIGNED DEFAULT 0,
    deleted_at TIMESTAMP NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status_published (status, published_at),
    INDEX idx_featured (is_featured, published_at),
    FULLTEXT INDEX idx_search (title, excerpt, content)
);
```

#### content_reads table
```sql
CREATE TABLE content_reads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    readable_type VARCHAR(255) NOT NULL,
    readable_id BIGINT UNSIGNED NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_read (user_id, readable_type, readable_id),
    INDEX idx_readable (readable_type, readable_id)
);
```

#### content_revisions table
```sql
CREATE TABLE content_revisions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    content_type VARCHAR(255) NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,
    editor_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    revision_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (editor_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_content (content_type, content_id, created_at)
);
```

#### content_categories table
```sql
CREATE TABLE content_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Announcement creation data integrity
*For any* valid announcement data and author, creating an announcement should result in a record with correct title, content, author_id, and status set.
**Validates: Requirements 1.1, 1.3**

### Property 2: Empty content validation
*For any* announcement or news with empty title or content, creation should be rejected with validation error.
**Validates: Requirements 1.5**

### Property 3: Publication timestamp tracking
*For any* content being published, the published_at timestamp should be set to the current time.
**Validates: Requirements 1.4**

### Property 4: Target audience notification accuracy
*For any* published announcement with target audience, notifications should be sent only to users matching the target criteria.
**Validates: Requirements 2.3, 2.4, 2.5**

### Property 5: News creation data completeness
*For any* valid news data, creating news should result in a record with title, content, excerpt, featured_image, author_id, and timestamp.
**Validates: Requirements 3.1, 3.5**

### Property 6: Category and tag associations
*For any* news with categories and tags, the associations should be persisted correctly in pivot tables.
**Validates: Requirements 3.2, 3.3**

### Property 7: Published content visibility
*For any* published news or announcement, the content should appear in the respective feed for authorized users.
**Validates: Requirements 3.4, 4.3**

### Property 8: News feed sorting
*For any* news feed request, news should be returned ordered by published_at descending.
**Validates: Requirements 4.1**

### Property 9: List view data completeness
*For any* news in the feed, the displayed data should include title, excerpt, featured_image, author, and published_at.
**Validates: Requirements 4.2**

### Property 10: Pagination limit enforcement
*For any* content list request, the number of items returned should not exceed 15 per page.
**Validates: Requirements 4.4**

### Property 11: Unread status tracking
*For any* announcement not yet read by a user, the unread status should be true for that user.
**Validates: Requirements 4.5**

### Property 12: Read tracking on view
*For any* user opening an announcement, a content_read record should be created with the user_id and announcement_id.
**Validates: Requirements 5.2, 5.5**

### Property 13: View counter increment
*For any* news article being viewed, the views_count should increment by exactly 1.
**Validates: Requirements 5.4**

### Property 14: Edit revision history
*For any* content edit, a revision record should be created with the previous content and editor information.
**Validates: Requirements 6.2**

### Property 15: Soft delete preservation
*For any* deleted content, the record should remain in database with deleted_at set and deleted_by recorded.
**Validates: Requirements 6.3, 6.5**

### Property 16: Draft status visibility
*For any* content with status 'draft', the content should not appear in public feeds.
**Validates: Requirements 6.4**

### Property 17: Read rate calculation
*For any* announcement, the read_rate should equal (users who read / target users) * 100.
**Validates: Requirements 7.2**

### Property 18: Unread users tracking
*For any* announcement, the list of unread users should include all target users who don't have a content_read record.
**Validates: Requirements 7.3**

### Property 19: Trending calculation
*For any* news, the trending score should be based on views_count within a time window.
**Validates: Requirements 7.4**

### Property 20: Search result matching
*For any* search query, all returned results should contain the query terms in title or content.
**Validates: Requirements 8.1**

### Property 21: Filter application
*For any* search with category or date range filters, all results should match the filter criteria.
**Validates: Requirements 8.2**

### Property 22: Scheduled publication automation
*For any* content with scheduled_at in the past, running the scheduler should change status to 'published' and set published_at.
**Validates: Requirements 9.2**

### Property 23: Schedule cancellation
*For any* scheduled content, canceling the schedule should change status back to 'draft' and clear scheduled_at.
**Validates: Requirements 9.4**

### Property 24: Course announcement association
*For any* course announcement created by an instructor, the announcement should be associated with the correct course_id.
**Validates: Requirements 10.1**

### Property 25: Instructor authorization
*For any* instructor creating a course announcement, the system should validate that the instructor has access to that course.
**Validates: Requirements 10.4**

## Error Handling

### Error Types

1. **Authorization Errors (403)**
   - User not authorized to create/edit content
   - Instructor doesn't have access to course

2. **Validation Errors (422)**
   - Empty title or content
   - Invalid target audience configuration
   - Invalid scheduled date (past date)

3. **Not Found Errors (404)**
   - Content not found
   - Category not found

## Testing Strategy

### Unit Testing
- Model relationships and scopes
- Service method logic
- Validation rules
- Helper methods

### Property-Based Testing
Using **Pest PHP** with minimum 100 iterations per test.

Key property tests:
- Content creation data integrity
- Notification targeting accuracy
- Read tracking consistency
- Statistics calculation accuracy
- Search and filter correctness

### Integration Testing
- End-to-end content publication flow
- Scheduled publishing automation
- Notification delivery
- Read tracking across multiple users

## API Endpoints

```
# Announcements
GET    /api/announcements
POST   /api/announcements
GET    /api/announcements/{id}
PUT    /api/announcements/{id}
DELETE /api/announcements/{id}
POST   /api/announcements/{id}/publish
POST   /api/announcements/{id}/schedule
POST   /api/announcements/{id}/read

# News
GET    /api/news
POST   /api/news
GET    /api/news/{slug}
PUT    /api/news/{slug}
DELETE /api/news/{slug}
POST   /api/news/{slug}/publish
GET    /api/news/trending

# Course Announcements
GET    /api/courses/{course}/announcements
POST   /api/courses/{course}/announcements

# Statistics
GET    /api/content/statistics
GET    /api/announcements/{id}/statistics

# Search
GET    /api/content/search
```

## Integration Points

1. **Auth Module** - User authentication and authorization
2. **Schemes Module** - Course association for announcements
3. **Enrollments Module** - Target audience determination
4. **Notifications Module** - Notification delivery

## Performance Considerations

- Full-text search indexes on title and content
- Cache news feed for 5 minutes
- Cache statistics for 1 hour
- Eager load relationships to avoid N+1
- Queue notification sending for large audiences

## Security Considerations

- Sanitize HTML content to prevent XSS
- Validate target audience configuration
- Rate limit content creation
- Log all moderation actions
- Verify instructor-course relationship
