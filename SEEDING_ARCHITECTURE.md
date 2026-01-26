# Seeding Architecture Documentation

## Overview

This document describes the comprehensive seeding architecture for the TA Prep LSP backend application. The seeders are designed to create realistic, interconnected test data across 6 interconnected modules.

## Module Dependency Order

```
┌─────────────────────────────────────────┐
│ Database Setup & Core Configuration    │
│ (Role, Permission, Settings, Category) │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Auth Module                             │
│ • 1000+ users (admins, instructors,     │
│   students) with all status types      │
│ • Privacy settings per user             │
│ • User activities & audit logs          │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Schemes Module                          │
│ • 50 courses (published, draft)         │
│ • 5-8 units per course                 │
│ • 10-15 lessons per unit               │
│ • 200 instructor-course assignments    │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Enrollments Module                      │
│ • 500-800 student enrollments          │
│ • Progress tracking (70% active,        │
│   15% pending, 15% completed)          │
│ • Course/Unit/Lesson progress records  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Learning Module                         │
│ • 5-8 assignments per lesson           │
│ • 50-70% student submission rate       │
│ • Multiple submissions & resubmissions │
│ • Status: draft, submitted, graded     │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Grading Module                          │
│ • Grades for 70-80% submissions        │
│ • Appeals for 5-10% of grades          │
│ • Appeal distribution: 40% pending,    │
│   30% approved, 30% rejected           │
└─────────────────────────────────────────┘
```

## Module-by-Module Breakdown

### 1. Auth Module
**Seeders:** `AuthComprehensiveDataSeeder`, `ProfileSeeder`

#### AuthComprehensiveDataSeeder
- Creates 1000+ users with realistic distribution:
  - Superadmins: 5
  - Admins: 15
  - Instructors: 80-100
  - Students: 900+
- All user status types: active, inactive, suspended, pending_verification
- JWT refresh tokens, OTP codes, password reset tokens
- Profile audit logs and login activities

#### ProfileSeeder
- Creates privacy settings for each user (role-aware visibility)
- Generates 5-30 activities per active student (based on enrollment count)
- Activity types: ENROLLMENT, COMPLETION, SUBMISSION, ACHIEVEMENT, BADGE_EARNED, CERTIFICATE_EARNED
- Activities distributed across last 90 days

**Data Generated:** 1000+ user records, privacy settings, audit logs, activities

---

### 2. Schemes Module
**Seeders:** `CourseSeeder`

#### CourseFactory
State methods:
- `published()` - Active courses ready for enrollment
- `draft()` - In-development courses
- `openEnrollment()` - Courses with open enrollment windows
- `online()` - Pure online delivery
- `hybrid()` - Blended online/offline

#### CourseSeeder
- Creates 50 courses with mixed statuses
- Generates 5-8 units per course (via UnitFactory)
- Generates 10-15 lessons per unit (via LessonFactory)
- Assigns 200 instructor-course relationships via courseAdmins pivot table
- Validates instructors exist before assignment

**Data Generated:**
- 50 courses
- 250-400 units
- 2500-6000 lessons
- 200 instructor assignments

---

### 3. Enrollments Module
**Seeders:** `EnrollmentSeeder`

#### EnrollmentFactory
State methods:
- `active()` - Active enrollments (70%)
- `pending()` - Pending approval (15%)
- `completed()` - Completed courses (15%)
- `forUser(User)` - Assign to specific user
- `forCourse(Course)` - Assign to specific course

#### EnrollmentSeeder
- Creates 500-800 student enrollments
- Status distribution:
  - 70% Active
  - 15% Pending
  - 15% Completed
- Progress initialization:
  - CourseProgress (track overall course progress)
  - UnitProgress for each unit (per enrolled student per unit)
  - LessonProgress for each lesson (per enrolled student per lesson)
- Validates student enrollment before progress creation

**Data Generated:**
- 500-800 enrollment records
- 500-800 course progress records
- 2500-4000 unit progress records
- 25000-60000 lesson progress records

---

### 4. Learning Module
**Seeders:** `AssignmentAndSubmissionSeeder`

#### SubmissionFactory
State methods:
- `draft()` - In-progress submissions
- `submitted()` - Submitted but not graded
- `graded()` - Graded submissions
- `late()` - Late submissions
- `resubmission()` - Resubmission with prev_submission_id
- `withScore(float)` - Set specific score
- `attempt(int)` - Set attempt number

#### AssignmentAndSubmissionSeeder
- Creates 5-8 assignments per lesson
- 50-70% of enrolled students submit assignments
- Multiple submission support:
  - Primary submission
  - Resubmissions (tracked via attempt_number, is_resubmission)
- Submission statuses: draft, submitted, graded
- Validates student enrollment before submission creation

**Data Generated:**
- 12500-30000 assignments (50 courses × 5-8 units × 10-15 lessons × 5-8 assignments)
- 6250-21000 submissions (50-70% submission rate × enrollments)
- Multiple submission variants per student

---

### 5. Grading Module
**Seeders:** `GradeAndAppealSeeder`

#### GradeAndAppealSeeder
- Creates grades for 70-80% of submitted/graded submissions
- Creates appeals for 5-10% of graded assignments
- Appeal status distribution:
  - 40% Pending
  - 30% Approved
  - 30% Rejected
- Links grades to:
  - Submissions
  - Assignments
  - Instructors (grade_by_id)
- Provides grade history and audit trail

**Data Generated:**
- 4375-16800 grade records
- 219-1680 appeal records
- Grade-to-submission mapping

---

## Data Relationships & Integrity

### Primary Keys & Foreign Keys
```
Users (id)
├── Enrollments (user_id) ──→ Courses (id)
│   ├── CourseProgress (enrollment_id)
│   ├── UnitProgress (enrollment_id) ──→ Units (id)
│   └── LessonProgress (enrollment_id) ──→ Lessons (id)
├── Assignments (created_by) ──→ Lessons (id)
├── Submissions (user_id, assignment_id)
│   ├── Assignment (id) ──→ Lesson (id)
│   └── Grades (submission_id)
│       └── Appeal (grade_id)
└── ProfilePrivacySetting (user_id)
└── UserActivity (user_id)
```

### Validation Rules
- Submissions can only be created for users enrolled in the course
- Assignments are created per lesson within enrolled courses
- Grades are only created for submitted/graded submissions
- Appeals are only created for graded assignments

---

## Seeder Execution Order

The main `DatabaseSeeder.php` executes seeders in this exact order:

```php
1. RoleSeeder                                    // Define roles
2. SuperAdminSeeder                              // Create superadmin user
3. ActiveUsersSeeder                             // Create active users
4. SystemSettingSeeder                           // System configuration
5. CategorySeeder                                // Content categories
6. MasterDataSeeder                              // Master data
7. AuthComprehensiveDataSeeder                   // 1000+ users
8. ProfileSeeder                                 // Privacy & activities
9. SchemesDatabaseSeeder → CourseSeeder          // 50 courses with units/lessons
10. EnrollmentsDatabaseSeeder → EnrollmentSeeder // 500-800 enrollments
11. LearningDatabaseSeeder → AssignmentSeeder    // Assignments & submissions
12. GradingDatabaseSeeder → GradeAndAppealSeeder // Grades & appeals
```

---

## Key Features

### Realistic Data Distribution
- **User Roles:** Realistic mix of admins, instructors, students
- **Enrollment Status:** 70% active, 15% pending, 15% completed
- **Submission Status:** 40% draft, 40% submitted, 20% graded
- **Appeal Status:** 40% pending, 30% approved, 30% rejected
- **Late Submissions:** 20% of submissions marked as late
- **Resubmissions:** 15% of submissions are resubmissions

### Temporal Coherence
- Activities distributed across last 90 days
- Submission dates within course timeline
- Enrollment approval dates before submission dates
- Grade dates after submission dates

### Relationship Integrity
- No orphaned records (all FKs point to valid records)
- Proper status progression (enrollment → assignment → submission → grade)
- Audit trails maintained via timestamps and audit tables

---

## Running the Seeders

### Full Seed (Recommended)
```bash
php artisan db:seed
```

### Individual Module Seeding
```bash
# Auth only
php artisan db:seed --class="Modules\Auth\Database\Seeders\AuthComprehensiveDataSeeder"

# Schemes only
php artisan db:seed --class="Modules\Schemes\Database\Seeders\SchemesDatabaseSeeder"

# Enrollments only
php artisan db:seed --class="Modules\Enrollments\Database\Seeders\EnrollmentsDatabaseSeeder"

# Learning only
php artisan db:seed --class="Modules\Learning\Database\Seeders\LearningDatabaseSeeder"

# Grading only
php artisan db:seed --class="Modules\Grading\Database\Seeders\GradingDatabaseSeeder"
```

### Reset Database & Reseed
```bash
php artisan migrate:fresh --seed
```

---

## Performance Considerations

### Database Size
- **Users:** 1000+
- **Courses:** 50
- **Enrollments:** 500-800
- **Assignments:** 12,500-30,000
- **Submissions:** 6,250-21,000
- **Grades:** 4,375-16,800
- **Appeals:** 219-1,680
- **Total Records:** ~60,000-100,000+

### Seed Duration
- Expected runtime: 3-5 minutes (depending on server specs)
- Uses batch inserts where possible
- WithoutModelEvents trait to skip event listeners

### Recommendations
- Run seeding on development machine (not production!)
- Ensure sufficient disk space (>500MB recommended)
- Consider running `php artisan migrate:fresh` before seeding
- Use `--force` flag in production-like environments only if needed

---

## Factory Usage Examples

### Creating Users
```php
// Create single user
User::factory()->create();

// Create active student
User::factory()->student()->active()->create();

// Create multiple instructors
User::factory()->instructor()->count(10)->create();
```

### Creating Enrollments
```php
// Create enrollment for specific user and course
Enrollment::factory()
    ->forUser($user)
    ->forCourse($course)
    ->active()
    ->create();

// Create pending enrollments
Enrollment::factory()
    ->count(50)
    ->pending()
    ->create();
```

### Creating Submissions
```php
// Create graded submission
Submission::factory()
    ->forUser($student)
    ->forAssignment($assignment)
    ->graded()
    ->withScore(85)
    ->create();

// Create resubmission
Submission::factory()
    ->forUser($student)
    ->forAssignment($assignment)
    ->resubmission()
    ->create();
```

---

## Testing with Seeders

The comprehensive seeders provide excellent data for testing:

```php
// Test enrollment workflows
$student = User::factory()->student()->create();
$course = Course::factory()->published()->create();
$enrollment = Enrollment::factory()->forUser($student)->forCourse($course)->create();

// Test submission workflows
$assignment = Assignment::factory()->forLesson($lesson)->create();
$submission = Submission::factory()
    ->forUser($student)
    ->forAssignment($assignment)
    ->submitted()
    ->create();

// Test grading workflows
$grade = Grade::factory()->forSubmission($submission)->create();
$appeal = Appeal::factory()->forGrade($grade)->pending()->create();
```

---

## Troubleshooting

### Foreign Key Constraint Errors
- Ensure seeders run in correct order (Auth → Schemes → Enrollments → Learning → Grading)
- Verify all parent records exist before creating child records

### Missing Data in Seeded Database
- Check console output for errors during seeding
- Verify factories have correct relationships
- Ensure timestamps are properly set

### Slow Seeding Performance
- Consider chunking large inserts
- Profile database queries with `php artisan tinker`
- Check server resources (CPU, RAM, disk I/O)

---

## Future Enhancements

- [ ] Add progress tracking statistics
- [ ] Create course schedule/calendar seeders
- [ ] Add announcement/notification seeders
- [ ] Create forum discussion seeders
- [ ] Add achievement/badge distribution seeders
- [ ] Create analytics snapshot seeders

---

**Last Updated:** January 25, 2026
**Version:** 1.0
**Status:** Complete & Integrated
