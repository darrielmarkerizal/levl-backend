# API Filters and Sorts Documentation

**Generated:** December 11, 2025 at 5:00 AM

This document lists all available filter and sort parameters for API endpoints.

## Table of Contents

- [Overview](#overview)
- [Filter Operators](#filter-operators)
- [Sort Syntax](#sort-syntax)
- [Endpoints](#endpoints)

---

## Overview

All API endpoints support standardized filtering and sorting through query parameters.

### Basic Usage

```
GET /api/resource?filter[field]=value&sort=-created_at&per_page=20
```

### Parameters

- `filter[field]` - Filter by field value
- `sort` - Sort by field (prefix with `-` for descending)
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15)
- `search` - Full-text search (where supported)

---

## Filter Operators

Filters support various operators for different comparison types:

| Operator | SQL Equivalent | Example | Description |
|----------|---------------|---------|-------------|
| (none) or `eq` | `=` | `filter[status]=published` | Exact match (default) |
| `neq` | `!=` | `filter[status]=neq:draft` | Not equal |
| `gt` | `>` | `filter[views]=gt:100` | Greater than |
| `gte` | `>=` | `filter[views]=gte:100` | Greater than or equal |
| `lt` | `<` | `filter[views]=lt:1000` | Less than |
| `lte` | `<=` | `filter[views]=lte:1000` | Less than or equal |
| `like` | `LIKE` | `filter[title]=like:%keyword%` | Partial match |
| `in` | `IN` | `filter[status]=in:draft,published` | Multiple values |
| `between` | `BETWEEN` | `filter[created_at]=between:2025-01-01,2025-12-31` | Range |

### Examples

```
# Exact match
GET /api/courses?filter[status]=published

# Greater than
GET /api/courses?filter[views_count]=gt:100

# Multiple values
GET /api/courses?filter[level_tag]=in:beginner,intermediate

# Date range
GET /api/courses?filter[created_at]=between:2025-01-01,2025-12-31

# Combine multiple filters
GET /api/courses?filter[status]=published&filter[level_tag]=beginner&filter[views_count]=gt:50
```

---

## Sort Syntax

Sorting is controlled by the `sort` parameter:

- **Ascending:** `sort=field_name`
- **Descending:** `sort=-field_name` (prefix with `-`)

### Examples

```
# Sort by created_at ascending
GET /api/courses?sort=created_at

# Sort by created_at descending
GET /api/courses?sort=-created_at

# Sort by title ascending
GET /api/courses?sort=title
```

If no sort parameter is provided, the endpoint's default sort will be applied.

---

## Endpoints

### Common Module

#### `/api/common/category`

**Repository:** `Modules\Common\Repositories\CategoryRepository`  
**Approach:** Unknown

**Allowed Filters:**

- `name`
- `value`
- `description`
- `status`

**Allowed Sorts:**

- `name`
- `value`
- `status`
- `created_at`
- `updated_at`

**Default Sort:** `-created_at`

**Example:**

```
GET /api/common/category?filter[name]=value&sort=-name
```

---

### Core Module

#### `/api/activity-log`

**Repository:** `App\Repositories\ActivityLogRepository`  
**Approach:** Unknown

**Allowed Filters:**

- `log_name`
- `event`
- `subject_type`
- `subject_id`
- `causer_type`
- `causer_id`

**Allowed Sorts:**

- `id`
- `created_at`
- `event`
- `log_name`

**Default Sort:** `-created_at`

**Example:**

```
GET /api/activity-log?filter[log_name]=value&sort=-id
```

---

#### `/api/master-data`

**Repository:** `App\Repositories\MasterDataRepository`  
**Approach:** Unknown

**Allowed Filters:**

- `is_active`
- `is_system`
- `value`
- `label`

**Allowed Sorts:**

- `value`
- `label`
- `sort_order`
- `created_at`
- `updated_at`

**Default Sort:** `sort_order`

**Example:**

```
GET /api/master-data?filter[is_active]=value&sort=-value
```

---

### Enrollments Module

#### `/api/enrollments/enrollment`

**Repository:** `Modules\Enrollments\Repositories\EnrollmentRepository`  
**Approach:** Unknown

**Allowed Filters:**

- `status`
- `user_id`
- `course_id`
- `enrolled_at`
- `completed_at`

**Allowed Sorts:**

- `id`
- `created_at`
- `updated_at`
- `status`
- `enrolled_at`
- `completed_at`
- `progress_percent`

**Default Sort:** `-created_at`

**Example:**

```
GET /api/enrollments/enrollment?filter[status]=value&sort=-id
```

---

### Schemes Module

#### `/api/schemes/course`

**Repository:** `Modules\Schemes\Repositories\CourseRepository`  
**Approach:** Unknown

**Allowed Filters:**

- `status`
- `level_tag`
- `type`
- `category_id`

**Allowed Sorts:**

- `id`
- `code`
- `title`
- `created_at`
- `updated_at`
- `published_at`

**Default Sort:** `title`

**Example:**

```
GET /api/schemes/course?filter[status]=value&sort=-id
```

---

#### `/api/schemes/lesson`

**Repository:** `Modules\Schemes\Repositories\LessonRepository`  
**Approach:** Unknown

**Allowed Filters:**

- `status`
- `content_type`

**Allowed Sorts:**

- `id`
- `title`
- `order`
- `status`
- `duration_minutes`
- `created_at`
- `updated_at`
- `published_at`

**Default Sort:** `order`

**Example:**

```
GET /api/schemes/lesson?filter[status]=value&sort=-id
```

---

#### `/api/schemes/unit`

**Repository:** `Modules\Schemes\Repositories\UnitRepository`  
**Approach:** Unknown

**Allowed Filters:**

- `status`

**Allowed Sorts:**

- `id`
- `code`
- `title`
- `order`
- `status`
- `created_at`
- `updated_at`

**Default Sort:** `order`

**Example:**

```
GET /api/schemes/unit?filter[status]=value&sort=-id
```

---

