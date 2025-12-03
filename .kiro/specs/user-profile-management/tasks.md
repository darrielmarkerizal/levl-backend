# Implementation Plan - User Profile Management System

## 1. Extend Auth Module Structure

- [ ] 1.1 Create new directories in Auth module
  - Create Traits directory
  - Verify Services directory exists
  - _Requirements: All_

- [ ] 1.2 Create database migrations
  - Create migration to add profile fields to users table
  - Create profile_privacy_settings table migration
  - Create user_activities table migration
  - Create pinned_badges table migration
  - Create profile_audit_logs table migration
  - _Requirements: 1.1, 4.1, 5.1, 6.5, 10.3_

- [ ] 1.3 Run migrations and verify database schema
  - Execute migrations
  - Verify foreign keys and indexes
  - _Requirements: All_

## 2. Implement Core Models and Traits

- [ ] 2.1 Extend User model with profile fields
  - Add profile fields to fillable
  - Add avatar_url accessor using UploadService
  - Add relationships (privacySettings, activities, pinnedBadges, auditLogs)
  - Add scopes (active, suspended)
  - _Requirements: 1.1, 2.1, 2.2_

- [ ] 2.2 Create HasProfilePrivacy trait (REUSABLE)
  - Implement canBeViewedBy method
  - Implement getVisibleFieldsFor method
  - _Requirements: 3.1, 3.3, 3.5_

- [ ] 2.3 Create TracksUserActivity trait (REUSABLE)
  - Implement activity logging methods
  - _Requirements: 5.1, 5.2_

- [ ] 2.4 Create ProfilePrivacySetting model
  - Define fillable fields and casts
  - Implement user relationship
  - Add helper methods (isPublic, canShowField)
  - _Requirements: 4.1, 4.2, 4.3_

- [ ] 2.5 Write property test for privacy settings
  - **Property 10: Privacy settings persistence**
  - **Validates: Requirements 4.1, 4.4**

- [ ] 2.6 Create UserActivity model
  - Define fillable fields and casts
  - Implement relationships (user, related morphTo)
  - Add scopes (ofType, inDateRange)
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 2.7 Create PinnedBadge model
  - Define fillable fields
  - Implement relationships
  - _Requirements: 6.5_

- [ ] 2.8 Create ProfileAuditLog model
  - Define fillable fields and casts
  - Implement relationships
  - _Requirements: 10.3, 10.4_

## 3. Implement Reusable Services

- [ ] 3.1 Create ProfileStatisticsService (REUSABLE)
  - Implement getStatistics aggregating from multiple modules
  - Implement getEnrollmentStats (from Enrollments module)
  - Implement getGamificationStats (from Gamification module)
  - Implement getPerformanceStats
  - Implement calculateCompletionRate
  - Implement calculateAverageScore
  - _Requirements: 7.1, 7.2, 7.3_

- [ ] 3.2 Write property tests for statistics
  - **Property 22: Enrollment statistics accuracy**
  - **Property 23: Gamification statistics accuracy**
  - **Property 24: Completion rate calculation**
  - **Validates: Requirements 7.1, 7.2, 7.3**

- [ ] 3.3 Create ProfilePrivacyService (REUSABLE)
  - Implement updatePrivacySettings
  - Implement getPrivacySettings
  - Implement canViewProfile
  - Implement canViewField
  - Implement filterProfileData
  - Implement isFieldVisible (REUSABLE for other modules)
  - _Requirements: 3.1, 3.3, 3.5, 4.1, 4.3, 4.4_

- [ ] 3.4 Write property tests for privacy
  - **Property 8: Privacy-based field filtering**
  - **Property 11: Field-level privacy enforcement**
  - **Validates: Requirements 3.1, 3.3, 3.5, 4.3**

- [ ] 3.5 Create UserActivityService (REUSABLE)
  - Implement logActivity
  - Implement getActivities with filters
  - Implement getRecentActivities
  - Implement logEnrollment (REUSABLE for Enrollments module)
  - Implement logCompletion (REUSABLE for Learning module)
  - Implement logSubmission (REUSABLE for Learning module)
  - Implement logAchievement (REUSABLE for Gamification module)
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [ ] 3.6 Write property tests for activity tracking
  - **Property 12: Activity history sorting**
  - **Property 13: Activity types inclusion**
  - **Validates: Requirements 5.1, 5.2**

## 4. Implement Main ProfileService

- [ ] 4.1 Create ProfileService
  - Inject UploadService, ProfileStatisticsService, ProfilePrivacyService, UserActivityService
  - Implement updateProfile with validation
  - Implement uploadAvatar using UploadService
  - Implement getProfileData
  - Implement getPublicProfile with privacy filtering
  - _Requirements: 1.1, 1.2, 2.1, 3.1, 3.2_

- [ ] 4.2 Write property tests for profile operations
  - **Property 1: Profile update data integrity**
  - **Property 2: Avatar upload and processing**
  - **Property 3: Email and phone validation**
  - **Validates: Requirements 1.1, 1.2, 1.3, 1.4**

- [ ] 4.3 Implement password management in ProfileService
  - Implement changePassword with current password verification
  - Implement password strength validation
  - Implement session invalidation
  - _Requirements: 8.1, 8.2, 8.3, 8.5_

- [ ] 4.4 Write property tests for password management
  - **Property 26: Current password verification**
  - **Property 27: Password strength validation**
  - **Property 30: Session invalidation on password change**
  - **Validates: Requirements 8.1, 8.2, 8.5**

- [ ] 4.5 Implement account deletion in ProfileService
  - Implement deleteAccount with password confirmation
  - Implement soft delete logic
  - Implement data anonymization
  - Implement grace period logic
  - _Requirements: 9.1, 9.2, 9.3, 9.5_

- [ ] 4.6 Write property tests for account deletion
  - **Property 31: Account deletion confirmation**
  - **Property 32: Soft delete on account deletion**
  - **Property 33: Data anonymization**
  - **Validates: Requirements 9.1, 9.2, 9.3**

## 5. Implement Controllers and API

- [ ] 5.1 Create ProfileController
  - Implement index (get own profile)
  - Implement update (update profile)
  - Implement uploadAvatar
  - Implement deleteAvatar
  - _Requirements: 1.1, 2.1, 2.2_

- [ ] 5.2 Create ProfilePrivacyController
  - Implement index (get privacy settings)
  - Implement update (update privacy settings)
  - _Requirements: 4.1, 4.3, 4.4_

- [ ] 5.3 Create ProfileActivityController
  - Implement index (get activity history with filters)
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 5.4 Create ProfileAchievementController
  - Implement index (get badges and certificates)
  - Implement pinBadge
  - Implement unpinBadge
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 5.5 Create ProfileStatisticsController
  - Implement index (get statistics)
  - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [ ] 5.6 Create ProfilePasswordController
  - Implement update (change password)
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 5.7 Create ProfileAccountController
  - Implement destroy (delete account)
  - Implement restore (restore deleted account)
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 5.8 Create PublicProfileController
  - Implement show (view other user's profile)
  - Apply privacy filtering
  - _Requirements: 3.1, 3.2, 3.3, 3.5_

- [ ] 5.9 Create Admin ProfileController
  - Implement show (admin view user profile)
  - Implement update (admin edit user profile)
  - Implement suspend (suspend user account)
  - Implement activate (activate user account)
  - Implement auditLogs (view audit logs)
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 5.10 Create Form Request validators
  - UpdateProfileRequest
  - ChangePasswordRequest
  - UpdatePrivacySettingsRequest
  - DeleteAccountRequest
  - _Requirements: 1.1, 1.3, 4.1, 8.1, 9.1_

- [ ] 5.11 Define API routes
  - Define profile routes
  - Define privacy routes
  - Define activity routes
  - Define achievement routes
  - Define statistics routes
  - Define password routes
  - Define account routes
  - Define public profile routes
  - Define admin profile routes
  - Apply middleware
  - _Requirements: All_

## 6. Implement Events and Listeners

- [ ] 6.1 Create profile events
  - ProfileUpdated event
  - PasswordChanged event
  - AccountDeleted event
  - _Requirements: 1.4, 8.4, 9.4_

- [ ] 6.2 Create event listeners
  - SendEmailVerificationOnEmailChange
  - SendPasswordChangedNotification
  - SendAccountDeletedNotification
  - LogProfileUpdate (for audit)
  - _Requirements: 1.5, 8.4, 9.4, 10.3_

## 7. Implement Achievement Showcase Integration

- [ ] 7.1 Integrate with Gamification module
  - Fetch badges from user_badges table
  - Fetch gamification stats from user_gamification_stats table
  - Fetch learning streak from learning_streaks table
  - _Requirements: 6.1, 6.2, 7.2, 7.4_

- [ ] 7.2 Write property tests for achievement integration
  - **Property 17: Badge showcase from Gamification**
  - **Property 25: Learning streak display**
  - **Validates: Requirements 6.1, 7.4**

- [ ] 7.3 Integrate with Operations module
  - Fetch certificates from certificates table
  - _Requirements: 6.3_

- [ ] 7.4 Write property test for certificate integration
  - **Property 19: Certificate display from Operations**
  - **Validates: Requirements 6.3**

## 8. Implement Activity Tracking Integration

- [ ] 8.1 Create activity logging in other modules
  - Add activity logging to Enrollments module (on enrollment)
  - Add activity logging to Learning module (on completion, submission)
  - Add activity logging to Gamification module (on badge earned)
  - _Requirements: 5.2_

- [ ] 8.2 Write property test for activity logging
  - **Property 14: Activity data completeness**
  - **Validates: Requirements 5.3**

## 9. Implement Admin Features

- [ ] 9.1 Create admin authorization
  - Verify admin role for admin endpoints
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 9.2 Implement audit logging
  - Log all admin edits to profile_audit_logs
  - Log account suspension/activation
  - _Requirements: 10.3_

- [ ] 9.3 Write property tests for admin features
  - **Property 36: Admin privacy override**
  - **Property 38: Admin action logging**
  - **Property 40: Account suspension**
  - **Validates: Requirements 10.1, 10.3, 10.5**

## 10. Implement Default Privacy Settings

- [ ] 10.1 Create observer or listener for User creation
  - Create default privacy settings on user registration
  - _Requirements: 4.1_

- [ ] 10.2 Create migration to add privacy settings for existing users
  - Create default privacy settings for all existing users
  - _Requirements: 4.1_

## 11. Testing and Quality Assurance

- [ ] 11.1 Write integration tests
  - Test end-to-end profile update flow
  - Test privacy settings application
  - Test password change flow
  - Test account deletion and anonymization
  - Test admin actions
  - _Requirements: All_

- [ ] 11.2 Test statistics aggregation
  - Test data aggregation from multiple modules
  - Test completion rate calculation
  - Test statistics caching
  - _Requirements: 7.1, 7.2, 7.3_

- [ ] 11.3 Test privacy enforcement
  - Test field visibility based on privacy settings
  - Test public vs private profile views
  - Test admin override
  - _Requirements: 3.1, 3.3, 3.5, 10.1_

- [ ] 11.4 Test activity tracking
  - Test activity logging from different modules
  - Test activity filtering and pagination
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

## 12. Checkpoint - Ensure all tests pass
- Ensure all tests pass, ask the user if questions arise.

## 13. Documentation and Deployment

- [ ] 13.1 Update API documentation
  - Document all endpoints
  - Add request/response examples
  - Document reusable services for other modules
  - _Requirements: All_

- [ ] 13.2 Create seeder for testing
  - Create sample privacy settings
  - Create sample activities
  - Create sample pinned badges
  - _Requirements: All_

- [ ] 13.3 Performance optimization
  - Add database indexes
  - Implement caching for statistics (5 minutes)
  - Implement caching for privacy settings (10 minutes)
  - Optimize N+1 queries with eager loading
  - _Requirements: All_

- [ ] 13.4 Deploy and monitor
  - Deploy to staging
  - Test integration with existing modules
  - Monitor performance
  - Fix any issues
  - _Requirements: All_
