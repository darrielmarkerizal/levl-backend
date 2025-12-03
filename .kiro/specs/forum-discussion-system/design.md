# Design Document - Forum/Discussion System

## Overview

Forum/Discussion System adalah modul Laravel yang memungkinkan pengguna untuk berdiskusi dalam konteks skema pembelajaran. Sistem ini dibangun sebagai modul terpisah yang terintegrasi dengan modul Auth, Schemes, Enrollments, dan Notifications yang sudah ada.

Sistem ini menyediakan fitur thread diskusi, nested replies, moderasi, reactions, dan statistik untuk mendukung kolaborasi dan pembelajaran yang efektif.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     API Layer (Controllers)                  │
├─────────────────────────────────────────────────────────────┤
│                    Service Layer (Business Logic)            │
├─────────────────────────────────────────────────────────────┤
│                Repository Layer (Data Access)                │
├─────────────────────────────────────────────────────────────┤
│                     Model Layer (Eloquent)                   │
└─────────────────────────────────────────────────────────────┘

External Dependencies:
- Auth Module (User authentication & authorization)
- Schemes Module (Course/Scheme context)
- Enrollments Module (Access control)
- Notifications Module (Notification delivery)
```

### Module Structure

```
Modules/Forums/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ThreadController.php
│   │   │   ├── ReplyController.php
│   │   │   ├── ReactionController.php
│   │   │   └── ForumStatisticsController.php
│   │   ├── Requests/
│   │   │   ├── CreateThreadRequest.php
│   │   │   ├── UpdateThreadRequest.php
│   │   │   ├── CreateReplyRequest.php
│   │   │   └── UpdateReplyRequest.php
│   │   └── Middleware/
│   │       └── CheckForumAccess.php
│   ├── Models/
│   │   ├── Thread.php
│   │   ├── Reply.php
│   │   ├── Reaction.php
│   │   └── ForumStatistic.php
│   ├── Repositories/
│   │   ├── ThreadRepository.php
│   │   ├── ReplyRepository.php
│   │   └── ForumStatisticsRepository.php
│   ├── Services/
│   │   ├── ForumService.php
│   │   ├── ModerationService.php
│   │   └── ForumNotificationService.php
│   ├── Policies/
│   │   ├── ThreadPolicy.php
│   │   └── ReplyPolicy.php
│   └── Events/
│       ├── ThreadCreated.php
│       ├── ReplyCreated.php
│       ├── ThreadPinned.php
│       └── ReactionAdded.php
├── database/
│   └── migrations/
│       ├── create_threads_table.php
│       ├── create_replies_table.php
│       ├── create_reactions_table.php
│       └── create_forum_statistics_table.php
└── routes/
    └── api.php
```

## Components and Interfaces

### 1. Thread Model

**Responsibilities:**
- Represent forum thread entity
- Manage thread metadata (title, content, status)
- Handle relationships with replies, reactions, and scheme

**Key Methods:**
```php
class Thread extends Model
{
    // Relationships
    public function scheme(): BelongsTo;
    public function author(): BelongsTo;
    public function replies(): HasMany;
    public function reactions(): HasMany;
    
    // Scopes
    public function scopeForScheme($query, $schemeId);
    public function scopePinned($query);
    public function scopeResolved($query);
    
    // Methods
    public function isPinned(): bool;
    public function isClosed(): bool;
    public function isResolved(): bool;
    public function incrementViews(): void;
}
```

### 2. Reply Model

**Responsibilities:**
- Represent reply entity
- Support nested replies (parent-child relationship)
- Track accepted answer status

**Key Methods:**
```php
class Reply extends Model
{
    // Relationships
    public function thread(): BelongsTo;
    public function author(): BelongsTo;
    public function parent(): BelongsTo;
    public function children(): HasMany;
    public function reactions(): HasMany;
    
    // Methods
    public function isAcceptedAnswer(): bool;
    public function getDepth(): int;
    public function canHaveChildren(): bool;
}
```

### 3. Reaction Model

**Responsibilities:**
- Store user reactions to threads/replies
- Support multiple reaction types
- Enforce one reaction per user per content

**Key Methods:**
```php
class Reaction extends Model
{
    // Relationships
    public function user(): BelongsTo;
    public function reactable(): MorphTo;
    
    // Constants
    const TYPE_LIKE = 'like';
    const TYPE_HELPFUL = 'helpful';
    const TYPE_SOLVED = 'solved';
    
    // Methods
    public static function toggle($userId, $reactableType, $reactableId, $type): bool;
}
```

### 4. ForumService

**Responsibilities:**
- Orchestrate forum operations
- Handle business logic for threads and replies
- Coordinate with notification service

**Key Methods:**
```php
class ForumService
{
    public function createThread(array $data, User $user): Thread;
    public function updateThread(Thread $thread, array $data): Thread;
    public function deleteThread(Thread $thread, User $user): bool;
    public function createReply(Thread $thread, array $data, User $user, ?Reply $parent = null): Reply;
    public function updateReply(Reply $reply, array $data): Reply;
    public function deleteReply(Reply $reply, User $user): bool;
    public function getThreadsForScheme(int $schemeId, array $filters = []): Collection;
    public function searchThreads(string $query, int $schemeId): Collection;
}
```

### 5. ModerationService

**Responsibilities:**
- Handle moderation actions (pin, close, delete)
- Manage accepted answers
- Log moderation activities

**Key Methods:**
```php
class ModerationService
{
    public function pinThread(Thread $thread, User $moderator): bool;
    public function unpinThread(Thread $thread, User $moderator): bool;
    public function closeThread(Thread $thread, User $moderator): bool;
    public function openThread(Thread $thread, User $moderator): bool;
    public function markAsAcceptedAnswer(Reply $reply, User $instructor): bool;
    public function unmarkAcceptedAnswer(Reply $reply, User $instructor): bool;
    public function moderateDelete(Thread|Reply $content, User $moderator, string $reason): bool;
}
```

### 6. ForumNotificationService

**Responsibilities:**
- Send notifications for forum events
- Integrate with existing Notifications module
- Handle broadcast and targeted notifications

**Key Methods:**
```php
class ForumNotificationService
{
    public function notifyThreadCreated(Thread $thread): void;
    public function notifyReplyCreated(Reply $reply): void;
    public function notifyThreadPinned(Thread $thread): void;
    public function notifyReactionAdded(Reaction $reaction): void;
}
```

## Data Models

### Database Schema

#### threads table
```sql
CREATE TABLE threads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    scheme_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_closed BOOLEAN DEFAULT FALSE,
    is_resolved BOOLEAN DEFAULT FALSE,
    views_count INT UNSIGNED DEFAULT 0,
    replies_count INT UNSIGNED DEFAULT 0,
    last_activity_at TIMESTAMP NULL,
    edited_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (scheme_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_scheme_activity (scheme_id, last_activity_at),
    INDEX idx_pinned (is_pinned, last_activity_at),
    FULLTEXT INDEX idx_search (title, content)
);
```

#### replies table
```sql
CREATE TABLE replies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    thread_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    depth INT UNSIGNED DEFAULT 0,
    is_accepted_answer BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES replies(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_thread (thread_id, created_at),
    INDEX idx_parent (parent_id),
    INDEX idx_accepted (thread_id, is_accepted_answer)
);
```

#### reactions table
```sql
CREATE TABLE reactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    reactable_type VARCHAR(255) NOT NULL,
    reactable_id BIGINT UNSIGNED NOT NULL,
    type ENUM('like', 'helpful', 'solved') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_reaction (user_id, reactable_type, reactable_id, type),
    INDEX idx_reactable (reactable_type, reactable_id)
);
```

#### forum_statistics table
```sql
CREATE TABLE forum_statistics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    scheme_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    threads_count INT UNSIGNED DEFAULT 0,
    replies_count INT UNSIGNED DEFAULT 0,
    views_count INT UNSIGNED DEFAULT 0,
    avg_response_time_minutes INT UNSIGNED NULL,
    response_rate DECIMAL(5,2) NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (scheme_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_stat (scheme_id, user_id, period_start, period_end),
    INDEX idx_scheme_period (scheme_id, period_start, period_end)
);
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, I identified several areas where properties can be consolidated:

- Properties 1.1, 1.3, 2.1, and 2.3 all test data integrity for creation operations - these can be combined
- Properties 3.1, 3.5, 4.5 all test sorting/filtering behavior - can be consolidated
- Properties 7.1, 7.2, 7.3, 7.4, 7.5 all test notification triggering - can be combined into fewer properties
- Authorization checks (1.2, 2.5, 5.4, 6.5, 9.5) are edge cases handled by middleware

### Property 1: Thread creation preserves data integrity
*For any* valid user enrolled in a scheme and valid thread data, creating a thread should result in a thread record with correct title, content, author_id, scheme_id, and timestamps set.
**Validates: Requirements 1.1, 1.3**

### Property 2: Authorization enforcement for thread creation
*For any* user not enrolled in a scheme, attempting to create a thread in that scheme should be rejected with authorization error.
**Validates: Requirements 1.2, 1.4**

### Property 3: Reply creation preserves parent reference
*For any* valid reply data and parent thread or reply, creating a reply should result in a reply record with correct parent reference and depth calculation.
**Validates: Requirements 2.1, 2.3**

### Property 4: Nested reply depth limit enforcement
*For any* reply at maximum depth, attempting to create a child reply should be rejected.
**Validates: Requirements 2.2**

### Property 5: Thread list sorting by activity
*For any* scheme, retrieving threads should return them ordered by last_activity_at descending, with pinned threads appearing first.
**Validates: Requirements 3.1, 6.1**

### Property 6: Thread list completeness
*For any* thread in the list, the displayed data should include title, author, replies_count, and last_activity_at.
**Validates: Requirements 3.2**

### Property 7: Search matches query terms
*For any* search query and scheme, all returned threads should contain the query terms in either title or content.
**Validates: Requirements 3.3**

### Property 8: Pagination page size limit
*For any* thread list request, the number of threads returned should not exceed 20 per page.
**Validates: Requirements 3.4**

### Property 9: Thread access filtering by enrollment
*For any* user, retrieving threads should only return threads from schemes the user is enrolled in.
**Validates: Requirements 3.5**

### Property 10: Thread detail completeness
*For any* thread detail view, the response should include complete thread content, all replies in hierarchical structure, and author metadata.
**Validates: Requirements 4.1, 4.2, 4.3**

### Property 11: View counter increment
*For any* thread, opening the thread detail should increment the views_count by exactly 1.
**Validates: Requirements 4.4**

### Property 12: Edit timestamp tracking
*For any* thread or reply edit, the edited_at timestamp should be set to the current time and the edited flag should be true.
**Validates: Requirements 5.1, 5.2**

### Property 13: Soft delete preservation
*For any* deleted thread or reply, the record should remain in database with deleted_at set, but should not appear in normal queries.
**Validates: Requirements 5.3, 5.5**

### Property 14: Thread closure prevents new replies
*For any* closed thread, attempting to create a new reply should be rejected.
**Validates: Requirements 6.2**

### Property 15: Moderation logging
*For any* moderation action (pin, close, delete, edit), the action should be logged with moderator identity and timestamp.
**Validates: Requirements 6.3, 6.4**

### Property 16: Notification triggering for replies
*For any* reply created, a notification should be sent to the parent thread or reply author.
**Validates: Requirements 7.1, 7.2**

### Property 17: Broadcast notification for instructor threads
*For any* thread created by an instructor, notifications should be sent to all enrolled users in the scheme.
**Validates: Requirements 7.3**

### Property 18: Notification persistence
*For any* forum notification sent, a corresponding record should be created in the notifications system.
**Validates: Requirements 7.5**

### Property 19: Reaction toggle behavior
*For any* user and content, giving the same reaction twice should result in the reaction being removed.
**Validates: Requirements 8.2**

### Property 20: Reaction count accuracy
*For any* thread or reply, the displayed reaction counts should match the actual number of reactions of each type.
**Validates: Requirements 8.3**

### Property 21: Accepted answer exclusivity
*For any* thread, marking a reply as accepted answer should unmark any previously accepted answer.
**Validates: Requirements 9.4**

### Property 22: Thread resolution on accepted answer
*For any* thread with an accepted answer, the thread's is_resolved flag should be true.
**Validates: Requirements 9.3**

### Property 23: Statistics update on activity
*For any* thread or reply creation, the forum statistics for the scheme should be updated to reflect the new activity.
**Validates: Requirements 10.1, 10.2, 10.3**

### Property 24: Response rate calculation
*For any* scheme statistics, the response_rate should equal (threads with replies / total threads) * 100.
**Validates: Requirements 10.4**

## Error Handling

### Error Types

1. **Authorization Errors (403)**
   - User not enrolled in scheme
   - User not author of content
   - User not moderator

2. **Validation Errors (422)**
   - Empty title or content
   - Invalid parent reply
   - Maximum depth exceeded
   - Thread is closed

3. **Not Found Errors (404)**
   - Thread not found
   - Reply not found
   - Scheme not found

4. **Conflict Errors (409)**
   - Duplicate reaction

### Error Response Format

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Validation error details"]
    }
}
```

## Testing Strategy

### Unit Testing

Unit tests will cover:
- Model relationships and scopes
- Service method logic
- Policy authorization rules
- Validation rules in requests
- Helper methods and utilities

Example unit tests:
- `ThreadTest::test_thread_belongs_to_scheme()`
- `ReplyTest::test_depth_calculation()`
- `ReactionTest::test_toggle_behavior()`
- `ThreadPolicyTest::test_user_can_edit_own_thread()`

### Property-Based Testing

We will use **Pest PHP** with **Pest Property Testing** plugin for property-based tests. Each property-based test will run a minimum of 100 iterations.

Property-based tests will be tagged with comments referencing the design document properties:

```php
/**
 * Feature: forum-discussion-system, Property 1: Thread creation preserves data integrity
 */
test('thread creation preserves data integrity', function () {
    // Property test implementation
})->repeat(100);
```

Key property tests:
- Thread/reply creation data integrity
- Authorization enforcement across all operations
- Sorting and filtering consistency
- Notification triggering reliability
- Statistics calculation accuracy

### Integration Testing

Integration tests will verify:
- End-to-end thread creation flow with notifications
- Reply nesting with depth limits
- Moderation actions with logging
- Search functionality with filters
- Statistics aggregation

### Test Data Generators

We will create generators for:
- Random valid thread data
- Random valid reply data with varying depths
- Random user-scheme enrollment combinations
- Random reaction types and targets

## Performance Considerations

### Database Optimization

1. **Indexes**
   - Composite index on (scheme_id, last_activity_at) for thread listing
   - Composite index on (thread_id, created_at) for reply listing
   - Full-text index on (title, content) for search

2. **Query Optimization**
   - Eager load relationships to avoid N+1 queries
   - Use pagination for all list endpoints
   - Cache frequently accessed data (pinned threads, statistics)

3. **Denormalization**
   - Store replies_count on threads table
   - Store views_count on threads table
   - Store depth on replies table

### Caching Strategy

- Cache thread lists per scheme for 5 minutes
- Cache forum statistics for 1 hour
- Invalidate cache on thread/reply creation
- Use Redis for cache storage

## Security Considerations

1. **Authorization**
   - Verify enrollment before any forum access
   - Use Laravel Policies for all authorization checks
   - Implement middleware for scheme access control

2. **Input Validation**
   - Sanitize HTML content to prevent XSS
   - Validate content length limits
   - Rate limit thread/reply creation

3. **Data Privacy**
   - Soft delete preserves data for audit
   - Only show deleted content to moderators
   - Log all moderation actions

## API Endpoints

### Thread Endpoints

```
GET    /api/schemes/{scheme}/forum/threads
POST   /api/schemes/{scheme}/forum/threads
GET    /api/schemes/{scheme}/forum/threads/{thread}
PUT    /api/schemes/{scheme}/forum/threads/{thread}
DELETE /api/schemes/{scheme}/forum/threads/{thread}
POST   /api/schemes/{scheme}/forum/threads/{thread}/pin
POST   /api/schemes/{scheme}/forum/threads/{thread}/close
GET    /api/schemes/{scheme}/forum/threads/search
```

### Reply Endpoints

```
POST   /api/forum/threads/{thread}/replies
PUT    /api/forum/replies/{reply}
DELETE /api/forum/replies/{reply}
POST   /api/forum/replies/{reply}/accept
```

### Reaction Endpoints

```
POST   /api/forum/threads/{thread}/reactions
POST   /api/forum/replies/{reply}/reactions
DELETE /api/forum/reactions/{reaction}
```

### Statistics Endpoints

```
GET    /api/schemes/{scheme}/forum/statistics
GET    /api/forum/statistics/user
```

## Integration Points

### With Existing Modules

1. **Auth Module**
   - Use User model for authors and moderators
   - Leverage existing authentication middleware
   - Use role-based authorization

2. **Schemes Module**
   - Link threads to Course model (schemes)
   - Verify course existence
   - Use course slug for routing

3. **Enrollments Module**
   - Check enrollment status for access control
   - Get enrolled users for notifications
   - Verify user-scheme relationships

4. **Notifications Module**
   - Send notifications via existing NotificationService
   - Use notification templates
   - Leverage notification preferences

## Migration Strategy

1. Create Forums module structure
2. Run database migrations
3. Seed initial data (if needed)
4. Deploy API endpoints
5. Update frontend to consume new endpoints
6. Monitor performance and errors

## Future Enhancements

- File attachments in threads/replies
- Mentions (@username) with notifications
- Thread subscriptions
- Advanced search with filters
- Forum moderation dashboard
- Reputation system based on helpful answers
- Thread categories/tags
