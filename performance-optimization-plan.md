# Performance Optimization Plan for TA PREP LSP Backend Modules

## Executive Summary

Following the successful optimization of the Course module (reducing latency from 928ms to ~53ms), we will apply similar optimization techniques to the following modules:

- Auth Module
- Enrollments Module  
- Grading Module
- Learning Module
- Schemes Module (already optimized)

## Module-Specific Analysis & Optimization Strategy

### 1. Auth Module (`Modules/Auth/`)

#### Current Performance Characteristics:
- Contains user authentication, registration, profile management
- Likely has complex user relationship queries
- May include media operations for avatars/profile pictures
- Heavy use of policies and permissions

#### Optimization Opportunities:
- **Profile endpoints**: Similar to Course optimization, create lightweight profile index resources
- **User listing**: Implement optimized UserIndexResource with selective field loading
- **Relationship optimization**: Optimize user-role-permission queries
- **Media handling**: Optimize avatar/image loading similar to course media

#### Implementation Plan:
1. Create `UserProfileIndexResource` for list views
2. Optimize `UserResource` to avoid N+1 queries with roles/permissions
3. Implement efficient media loading for avatars
4. Optimize policy queries for authorization

```php
// Example optimized user index query
User::with(['roles:id,name', 'media:id,model_type,model_id,collection_name,file_name'])
     ->withCount('enrollments')
     ->paginate($perPage);
```

### 2. Enrollments Module (`Modules/Enrollments/`)

#### Current Performance Characteristics:
- Complex enrollment status tracking
- Progress tracking across courses/units/lessons
- Multiple related entities (CourseProgress, UnitProgress, LessonProgress)
- Likely has complex relationship queries

#### Optimization Opportunities:
- **Enrollment listing**: Create lightweight enrollment index resource
- **Progress tracking**: Optimize progress queries to avoid deep nesting
- **Relationship loading**: Optimize course-user-enrollment relationships

#### Implementation Plan:
1. Create `EnrollmentIndexResource` with essential fields only
2. Optimize progress tracking queries using eager loading
3. Implement efficient enrollment status computation
4. Optimize progress percentage calculations

```php
// Example optimized enrollment query
Enrollment::with([
    'user:id,name,email',
    'course:id,title,code',
    'courseProgress:id,enrollment_id,status,percentage'
])
->withCount(['unitProgress', 'lessonProgress'])
->paginate($perPage);
```

### 3. Grading Module (`Modules/Grading/`)

#### Current Performance Characteristics:
- Grade calculations and assessments
- Appeal processes with complex state management
- Potentially large datasets for grade analytics
- Multiple related entities (grades, appeals, rubrics)

#### Optimization Opportunities:
- **Grade listing**: Create optimized grade index resource
- **Analytics queries**: Optimize aggregate calculations
- **Appeal status tracking**: Efficient state queries

#### Implementation Plan:
1. Create `GradeIndexResource` for efficient grade listings
2. Optimize grade calculation queries using database aggregations
3. Implement efficient appeal status tracking
4. Optimize bulk grade operations

```php
// Example optimized grade query
Grade::with([
    'user:id,name,email',
    'assignment:id,title,type',
    'appeal:id,grade_id,status,submitted_at'
])
->select(['id', 'user_id', 'assignment_id', 'score', 'status', 'created_at'])
->paginate($perPage);
```

### 4. Learning Module (`Modules/Learning/`)

#### Current Performance Characteristics:
- Assignment and submission management
- Question/answer systems with complex relationships
- Submission state tracking
- Potentially large file uploads and media operations

#### Optimization Opportunities:
- **Assignment listing**: Create lightweight assignment index resource
- **Submission tracking**: Optimize submission status queries
- **Question/answer relationships**: Optimize complex nested queries
- **File/media operations**: Optimize attachment loading

#### Implementation Plan:
1. Create `AssignmentIndexResource` for efficient assignment listings
2. Optimize submission queries with selective relationship loading
3. Implement efficient question/answer relationship queries
4. Optimize file attachment loading for submissions

```php
// Example optimized assignment query
Assignment::with([
    'course:id,title',
    'lesson:id,title',
    'submissionStats:id,assignment_id,total_submissions,graded_submissions'
])
->withCount(['submissions', 'questions'])
->paginate($perPage);
```

### 5. Schemes Module (`Modules/Schemes/`)

#### Already Optimized:
- Course listing latency reduced from 928ms to ~53ms
- Implemented CourseIndexResource with optimized relationships
- Optimized media loading for thumbnails/banners
- Implemented efficient admin relationship queries

## Cross-Module Optimization Strategies

### 1. Shared Caching Strategies
- Implement module-specific caching services
- Use cache tags for efficient invalidation
- Implement smart cache warming strategies

### 2. Database Query Optimization
- Add strategic database indexes
- Optimize complex JOIN operations
- Implement query result caching

### 3. Relationship Loading Optimization
- Use `with()` method with specific field selection
- Implement lazy loading for non-critical relationships
- Use `withCount()` instead of counting relationships

### 4. Resource Transformation Optimization
- Create dedicated index resources for list views
- Implement conditional relationship loading
- Optimize media URL generation

## Implementation Priority

### Phase 1: High Impact, Low Complexity
1. Auth Module - User listing optimization
2. Enrollments Module - Enrollment listing optimization

### Phase 2: Medium Complexity
1. Learning Module - Assignment listing optimization
2. Grading Module - Grade listing optimization

### Phase 3: Complex Optimizations
1. Deep relationship optimizations across all modules
2. Advanced caching implementations
3. Database index optimizations

## Success Metrics

Target latency improvements for each module:
- **Auth Module**: User listing < 60ms
- **Enrollments Module**: Enrollment listing < 60ms  
- **Learning Module**: Assignment listing < 60ms
- **Grading Module**: Grade listing < 60ms
- **Schemes Module**: Already achieved < 60ms

## Implementation Approach

For each module, we will:
1. Identify the heaviest endpoints (using Telescope/Query Detector)
2. Create optimized index resources similar to CourseIndexResource
3. Optimize database queries with selective relationship loading
4. Test performance improvements using wrk or similar tools
5. Deploy incrementally with monitoring