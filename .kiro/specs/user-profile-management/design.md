# Design Document - User Profile Management System

## Overview

User Profile Management System adalah enhancement untuk modul Auth yang sudah ada, menambahkan fitur lengkap untuk mengelola profil pengguna, privacy settings, achievement showcase, dan activity history. Sistem ini mengintegrasikan data dari modul Gamification, Enrollments, Learning, dan Operations untuk memberikan view komprehensif tentang pengguna.

**PENTING:** Sistem ini TIDAK membuat modul baru, tetapi meng-extend modul Auth yang sudah ada dengan menambahkan:
- ProfileController di Auth module
- Profile-related services
- Privacy settings table
- Activity tracking
- Reusable functions untuk digunakan modul lain

## Architecture

### Extension to Auth Module

```
Modules/Auth/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php (existing)
│   │   │   ├── ProfileController.php (NEW)
│   │   │   ├── ProfilePrivacyController.php (NEW)
│   │   │   └── ProfileActivityController.php (NEW)
│   │   └── Requests/
│   │       ├── UpdateProfileRequest.php (NEW)
│   │       ├── ChangePasswordRequest.php (NEW)
│   │       └── UpdatePrivacySettingsRequest.php (NEW)
│   ├── Models/
│   │   ├── User.php (EXTEND existing)
│   │   ├── ProfilePrivacySetting.php (NEW)
│   │   ├── UserActivity.php (NEW)
│   │   ├── PinnedBadge.php (NEW)
│   │   └── ProfileAuditLog.php (NEW)
│   ├── Services/
│   │   ├── ProfileService.php (NEW)
│   │   ├── ProfileStatisticsService.php (NEW - REUSABLE)
│   │   ├── ProfilePrivacyService.php (NEW - REUSABLE)
│   │   └── UserActivityService.php (NEW - REUSABLE)
│   ├── Traits/
│   │   ├── HasProfilePrivacy.php (NEW - REUSABLE)
│   │   └── TracksUserActivity.php (NEW - REUSABLE)
│   └── Events/
│       ├── ProfileUpdated.php (NEW)
│       ├── PasswordChanged.php (NEW)
│       └── AccountDeleted.php (NEW)
└── database/
    └── migrations/
        ├── add_profile_fields_to_users_table.php (NEW)
        ├── create_profile_privacy_settings_table.php (NEW)
        ├── create_user_activities_table.php (NEW)
        ├── create_pinned_badges_table.php (NEW)
        └── create_profile_audit_logs_table.php (NEW)
```

## Components and Interfaces

### 1. Extended User Model

**New Fields Added to users table:**
```php
// Add to existing users table migration
$table->text('bio')->nullable();
$table->string('avatar_path')->nullable();
$table->string('phone')->nullable();
$table->enum('account_status', ['active', 'suspended', 'deleted'])->default('active');
$table->timestamp('last_profile_update')->nullable();
$table->timestamp('email_verified_at')->nullable(); // if not exists
```

**Extended User Model:**
```php
class User extends Authenticatable
{
    use HasProfilePrivacy, TracksUserActivity; // NEW traits
    
    // NEW Relationships
    public function privacySettings(): HasOne;
    public function activities(): HasMany;
    public function pinnedBadges(): HasMany;
    public function auditLogs(): HasMany;
    
    // REUSE existing relationships from other modules
    public function badges(): HasMany; // from Gamification
    public function certificates(): HasMany; // from Operations
    public function enrollments(): HasMany; // from Enrollments
    public function courseProgress(): HasMany; // from Enrollments
    public function gamificationStats(): HasOne; // from Gamification
    
    // NEW Methods
    public function getAvatarUrlAttribute(): ?string;
    public function updateProfile(array $data): bool;
    public function changePassword(string $newPassword): bool;
    public function softDeleteAccount(): bool;
    public function canBeViewedBy(User $viewer): bool;
    public function getVisibleFieldsFor(User $viewer): array;
    
    // NEW Scopes
    public function scopeActive($query);
    public function scopeSuspended($query);
}
```

### 2. ProfilePrivacySetting Model

**Responsibilities:**
- Store user privacy preferences
- Control profile visibility
- Manage field-level privacy

```php
class ProfilePrivacySetting extends Model
{
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_FRIENDS = 'friends_only';
    
    protected $fillable = [
        'user_id',
        'profile_visibility',
        'show_email',
        'show_phone',
        'show_activity_history',
        'show_achievements',
        'show_statistics',
    ];
    
    protected $casts = [
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
        'show_activity_history' => 'boolean',
        'show_achievements' => 'boolean',
        'show_statistics' => 'boolean',
    ];
    
    public function user(): BelongsTo;
    public function isPublic(): bool;
    public function canShowField(string $field, User $viewer): bool;
}
```

### 3. UserActivity Model

**Responsibilities:**
- Track user activities across modules
- Provide activity history
- Support filtering and pagination

```php
class UserActivity extends Model
{
    const TYPE_ENROLLMENT = 'enrollment';
    const TYPE_COMPLETION = 'completion';
    const TYPE_SUBMISSION = 'submission';
    const TYPE_ACHIEVEMENT = 'achievement';
    const TYPE_BADGE_EARNED = 'badge_earned';
    const TYPE_CERTIFICATE_EARNED = 'certificate_earned';
    
    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_data',
        'related_type',
        'related_id',
        'created_at',
    ];
    
    protected $casts = [
        'activity_data' => 'array',
    ];
    
    public function user(): BelongsTo;
    public function related(): MorphTo;
    
    // Scopes
    public function scopeOfType($query, string $type);
    public function scopeInDateRange($query, $start, $end);
}
```

### 4. ProfileService (REUSABLE)

**Responsibilities:**
- Orchestrate profile operations
- Coordinate with other modules
- Handle profile updates and validation

```php
class ProfileService
{
    public function __construct(
        private UploadService $uploadService, // REUSE from app/Services
        private ProfileStatisticsService $statisticsService,
        private ProfilePrivacyService $privacyService,
        private UserActivityService $activityService
    ) {}
    
    public function updateProfile(User $user, array $data): User;
    public function uploadAvatar(User $user, UploadedFile $file): string;
    public function getProfileData(User $user, ?User $viewer = null): array;
    public function getPublicProfile(User $user, User $viewer): array;
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool;
    public function deleteAccount(User $user, string $password): bool;
    public function restoreAccount(User $user): bool;
}
```

### 5. ProfileStatisticsService (REUSABLE)

**Responsibilities:**
- Aggregate statistics from multiple modules
- Calculate derived metrics
- Provide reusable statistics for other features

```php
class ProfileStatisticsService
{
    public function getStatistics(User $user): array;
    public function getEnrollmentStats(User $user): array;
    public function getGamificationStats(User $user): array;
    public function getPerformanceStats(User $user): array;
    public function getActivityStats(User $user): array;
    
    // REUSABLE methods for other modules
    public function calculateCompletionRate(User $user): float;
    public function calculateAverageScore(User $user): float;
    public function getTotalPoints(User $user): int;
    public function getCurrentLevel(User $user): int;
}
```

### 6. ProfilePrivacyService (REUSABLE)

**Responsibilities:**
- Manage privacy settings
- Filter data based on privacy
- Provide reusable privacy checks

```php
class ProfilePrivacyService
{
    public function updatePrivacySettings(User $user, array $settings): ProfilePrivacySetting;
    public function getPrivacySettings(User $user): ProfilePrivacySetting;
    public function canViewProfile(User $user, User $viewer): bool;
    public function canViewField(User $user, string $field, User $viewer): bool;
    public function filterProfileData(array $data, User $user, User $viewer): array;
    
    // REUSABLE for other modules
    public function isFieldVisible(User $user, string $field, User $viewer): bool;
    public function getVisibleFields(User $user, User $viewer): array;
}
```

### 7. UserActivityService (REUSABLE)

**Responsibilities:**
- Track user activities
- Provide activity history
- Reusable for other modules to log activities

```php
class UserActivityService
{
    public function logActivity(User $user, string $type, array $data, ?Model $related = null): UserActivity;
    public function getActivities(User $user, array $filters = []): Collection;
    public function getRecentActivities(User $user, int $limit = 10): Collection;
    
    // REUSABLE methods for other modules
    public function logEnrollment(User $user, $course): void;
    public function logCompletion(User $user, $course): void;
    public function logSubmission(User $user, $assignment): void;
    public function logAchievement(User $user, $badge): void;
}
```

## Data Models

### Database Schema

#### Add to users table (migration)
```sql
ALTER TABLE users ADD COLUMN bio TEXT NULL;
ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN account_status ENUM('active', 'suspended', 'deleted') DEFAULT 'active';
ALTER TABLE users ADD COLUMN last_profile_update TIMESTAMP NULL;
```

#### profile_privacy_settings table
```sql
CREATE TABLE profile_privacy_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    profile_visibility ENUM('public', 'private', 'friends_only') DEFAULT 'public',
    show_email BOOLEAN DEFAULT FALSE,
    show_phone BOOLEAN DEFAULT FALSE,
    show_activity_history BOOLEAN DEFAULT TRUE,
    show_achievements BOOLEAN DEFAULT TRUE,
    show_statistics BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### user_activities table
```sql
CREATE TABLE user_activities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_data JSON NULL,
    related_type VARCHAR(255) NULL,
    related_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_type (user_id, activity_type, created_at),
    INDEX idx_related (related_type, related_id)
);
```

#### pinned_badges table
```sql
CREATE TABLE pinned_badges (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    badge_id BIGINT UNSIGNED NOT NULL,
    order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    INDEX idx_user_order (user_id, order)
);
```

#### profile_audit_logs table
```sql
CREATE TABLE profile_audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    changes JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_action (user_id, action, created_at),
    INDEX idx_admin (admin_id, created_at)
);
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Profile update data integrity
*For any* valid profile data, updating a user profile should result in correct values for name, email, phone, bio, and last_profile_update timestamp.
**Validates: Requirements 1.1, 1.4**

### Property 2: Avatar upload and processing
*For any* valid image file, uploading an avatar should result in a resized image stored at avatar_path with correct dimensions.
**Validates: Requirements 1.2**

### Property 3: Email and phone validation
*For any* profile update with email or phone, the system should validate format and reject invalid formats.
**Validates: Requirements 1.3**

### Property 4: Email verification triggering
*For any* email change, a verification email should be sent to the new email address.
**Validates: Requirements 1.5**

### Property 5: Own profile data completeness
*For any* user viewing their own profile, all profile fields should be visible including private data.
**Validates: Requirements 2.1, 2.2**

### Property 6: Achievement display integration
*For any* user profile, badges and certificates should be fetched from Gamification and Operations modules respectively.
**Validates: Requirements 2.3**

### Property 7: Statistics display integration
*For any* user profile, statistics should aggregate data from Enrollments, Gamification, and Learning modules.
**Validates: Requirements 2.4**

### Property 8: Privacy-based field filtering
*For any* user viewing another's profile, only fields allowed by privacy settings should be visible.
**Validates: Requirements 3.1, 3.3, 3.5**

### Property 9: Public profile data subset
*For any* public profile view, the displayed data should include only avatar, name, bio, and achievements (if allowed).
**Validates: Requirements 3.2**

### Property 10: Privacy settings persistence
*For any* privacy settings update, the settings should be saved correctly and immediately applied to profile views.
**Validates: Requirements 4.1, 4.4**

### Property 11: Field-level privacy enforcement
*For any* privacy setting for email, phone, or activity_history, the field should be hidden/shown according to the setting.
**Validates: Requirements 4.3**

### Property 12: Activity history sorting
*For any* activity history request, activities should be returned ordered by created_at descending.
**Validates: Requirements 5.1**

### Property 13: Activity types inclusion
*For any* activity history, the list should include enrollments, completions, submissions, and achievements.
**Validates: Requirements 5.2**

### Property 14: Activity data completeness
*For any* activity record, it should include timestamp, activity_type, and activity_data.
**Validates: Requirements 5.3**

### Property 15: Activity filtering
*For any* activity history with filters, results should match the activity_type and date_range filters.
**Validates: Requirements 5.4**

### Property 16: Activity pagination
*For any* activity history request, the number of activities returned should not exceed 20 per page.
**Validates: Requirements 5.5**

### Property 17: Badge showcase from Gamification
*For any* user, the badge showcase should display all badges from the Gamification module's user_badges table.
**Validates: Requirements 6.1**

### Property 18: Badge data completeness
*For any* displayed badge, it should include icon, name, and earned_at timestamp.
**Validates: Requirements 6.2**

### Property 19: Certificate display from Operations
*For any* user, certificates should be fetched from Operations module with download links.
**Validates: Requirements 6.3**

### Property 20: Achievement sorting
*For any* achievement showcase, items should be sorted by earned_at/created_at descending.
**Validates: Requirements 6.4**

### Property 21: Badge pinning
*For any* user pinning a badge, the badge should be marked as pinned and displayed prominently on profile.
**Validates: Requirements 6.5**

### Property 22: Enrollment statistics accuracy
*For any* user, enrollment statistics should match the count from Enrollments module.
**Validates: Requirements 7.1**

### Property 23: Gamification statistics accuracy
*For any* user, points, level, and badges count should match data from Gamification module.
**Validates: Requirements 7.2**

### Property 24: Completion rate calculation
*For any* user, completion_rate should equal (completed_courses / enrolled_courses) * 100.
**Validates: Requirements 7.3**

### Property 25: Learning streak display
*For any* user, learning streak should be fetched from Gamification module's learning_streaks table.
**Validates: Requirements 7.4**

### Property 26: Current password verification
*For any* password change request, the current password must be verified before allowing change.
**Validates: Requirements 8.1**

### Property 27: Password strength validation
*For any* new password, it must be at least 8 characters long.
**Validates: Requirements 8.2**

### Property 28: Password hashing
*For any* password change, the new password should be hashed using bcrypt before storage.
**Validates: Requirements 8.3**

### Property 29: Password change notification
*For any* successful password change, a confirmation email should be sent.
**Validates: Requirements 8.4**

### Property 30: Session invalidation on password change
*For any* password change, all sessions except the current one should be invalidated.
**Validates: Requirements 8.5**

### Property 31: Account deletion confirmation
*For any* account deletion request, the user must provide correct password for confirmation.
**Validates: Requirements 9.1**

### Property 32: Soft delete on account deletion
*For any* confirmed account deletion, the user record should be soft deleted with account_status='deleted'.
**Validates: Requirements 9.2**

### Property 33: Data anonymization
*For any* deleted account, user data in forum posts and submissions should be anonymized.
**Validates: Requirements 9.3**

### Property 34: Deletion notification
*For any* account deletion, a confirmation email should be sent.
**Validates: Requirements 9.4**

### Property 35: Grace period enforcement
*For any* deleted account, the data should be retained for 30 days before permanent deletion.
**Validates: Requirements 9.5**

### Property 36: Admin privacy override
*For any* admin viewing a user profile, all fields should be visible regardless of privacy settings.
**Validates: Requirements 10.1**

### Property 37: Admin edit permissions
*For any* admin editing a user profile, all fields except password should be editable.
**Validates: Requirements 10.2**

### Property 38: Admin action logging
*For any* admin edit, a profile_audit_log record should be created with admin_id and changes.
**Validates: Requirements 10.3**

### Property 39: Audit log display
*For any* admin viewing audit logs, all profile changes should be displayed with timestamps and admin info.
**Validates: Requirements 10.4**

### Property 40: Account suspension
*For any* admin suspending a user, the account_status should be set to 'suspended' and login should be prevented.
**Validates: Requirements 10.5**

## Error Handling

### Error Types

1. **Authorization Errors (403)**
   - User not authorized to view private profile
   - User not authorized to edit other's profile

2. **Validation Errors (422)**
   - Invalid email format
   - Invalid phone format
   - Weak password
   - Invalid privacy settings combination

3. **Authentication Errors (401)**
   - Incorrect current password
   - Account suspended

4. **Not Found Errors (404)**
   - User not found

## Testing Strategy

### Unit Testing
- Model relationships and scopes
- Service method logic
- Privacy filtering logic
- Statistics calculation
- Validation rules

### Property-Based Testing
Using **Pest PHP** with minimum 100 iterations per test.

Key property tests:
- Profile update data integrity
- Privacy enforcement across all scenarios
- Statistics aggregation accuracy
- Activity tracking consistency
- Password security

### Integration Testing
- End-to-end profile update flow
- Privacy settings application
- Statistics aggregation from multiple modules
- Account deletion and anonymization
- Admin actions and logging

## API Endpoints

```
# Profile Management
GET    /api/profile
PUT    /api/profile
POST   /api/profile/avatar
DELETE /api/profile/avatar

# Privacy Settings
GET    /api/profile/privacy
PUT    /api/profile/privacy

# Activity History
GET    /api/profile/activities

# Achievements
GET    /api/profile/achievements
POST   /api/profile/badges/{badge}/pin
DELETE /api/profile/badges/{badge}/unpin

# Statistics
GET    /api/profile/statistics

# Password Management
PUT    /api/profile/password

# Account Management
DELETE /api/profile/account
POST   /api/profile/account/restore

# Public Profile
GET    /api/users/{user}/profile

# Admin Endpoints
GET    /api/admin/users/{user}/profile
PUT    /api/admin/users/{user}/profile
POST   /api/admin/users/{user}/suspend
POST   /api/admin/users/{user}/activate
GET    /api/admin/users/{user}/audit-logs
```

## Integration Points (REUSING Existing Modules)

### 1. Auth Module (EXTEND)
- Extend User model with profile fields
- Add profile-related controllers
- Reuse authentication middleware

### 2. Gamification Module (READ)
- Fetch badges from `user_badges` table
- Fetch points and level from `user_gamification_stats` table
- Fetch learning streak from `learning_streaks` table
- **NO CHANGES to Gamification module**

### 3. Operations Module (READ)
- Fetch certificates from `certificates` table
- **NO CHANGES to Operations module**

### 4. Enrollments Module (READ)
- Fetch enrollments from `enrollments` table
- Fetch course progress from `course_progress` table
- **NO CHANGES to Enrollments module**

### 5. Learning Module (READ)
- Fetch submissions from `submissions` table
- **NO CHANGES to Learning module**

### 6. Notifications Module (USE)
- Send email notifications via existing NotificationService
- **NO CHANGES to Notifications module**

### 7. Common Services (REUSE)
- Use existing `UploadService` for avatar uploads
- **NO CHANGES needed**

## Reusable Functions for Other Modules

Other modules can use these services:

```php
// Check if user can view another user's data
$canView = app(ProfilePrivacyService::class)->canViewProfile($user, $viewer);

// Get user statistics
$stats = app(ProfileStatisticsService::class)->getStatistics($user);

// Log user activity
app(UserActivityService::class)->logActivity($user, 'completion', $data, $course);

// Check field visibility
$visible = app(ProfilePrivacyService::class)->isFieldVisible($user, 'email', $viewer);
```

## Performance Considerations

- Cache user statistics for 5 minutes
- Cache privacy settings for 10 minutes
- Eager load relationships to avoid N+1
- Index on user_activities for fast filtering
- Use Redis for session management

## Security Considerations

- Hash passwords with bcrypt
- Validate email format
- Sanitize bio content to prevent XSS
- Rate limit profile updates
- Log all admin actions
- Verify password before sensitive operations
- Implement CSRF protection

## Migration Strategy

1. Add profile fields to users table
2. Create new tables (privacy_settings, user_activities, etc.)
3. Create default privacy settings for existing users
4. Deploy new controllers and services
5. Update frontend to consume new endpoints
6. Monitor performance and errors

## Future Enhancements

- Social connections (friends/followers)
- Profile themes/customization
- Activity feed for friends
- Profile badges/achievements
- Two-factor authentication
- Profile completion percentage
