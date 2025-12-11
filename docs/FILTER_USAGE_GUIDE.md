# Filter and Sort Usage Guide

This guide provides comprehensive examples and best practices for using filters and sorting in the LMS API.

## Table of Contents

- [Quick Start](#quick-start)
- [Filter Operators](#filter-operators)
- [Sort Syntax](#sort-syntax)
- [Common Use Cases](#common-use-cases)
- [Advanced Filtering](#advanced-filtering)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)

---

## Quick Start

### Basic Filtering

Filter by a single field:

```http
GET /api/courses?filter[status]=published
```

### Basic Sorting

Sort by a field (ascending):

```http
GET /api/courses?sort=title
```

Sort by a field (descending):

```http
GET /api/courses?sort=-created_at
```

### Pagination

Control pagination:

```http
GET /api/courses?page=2&per_page=20
```

### Combining Parameters

Combine filters, sorting, and pagination:

```http
GET /api/courses?filter[status]=published&filter[level_tag]=beginner&sort=-created_at&page=1&per_page=15
```

---

## Filter Operators

### Exact Match (Default)

Match exact value:

```http
GET /api/courses?filter[status]=published
GET /api/courses?filter[status]=eq:published  # Explicit operator
```

### Not Equal

Exclude specific values:

```http
GET /api/courses?filter[status]=neq:draft
```

### Greater Than / Less Than

Numeric comparisons:

```http
# Greater than
GET /api/courses?filter[views_count]=gt:100

# Greater than or equal
GET /api/courses?filter[views_count]=gte:100

# Less than
GET /api/courses?filter[views_count]=lt:1000

# Less than or equal
GET /api/courses?filter[views_count]=lte:1000
```

### Like (Partial Match)

Search for partial matches:

```http
# Contains "Laravel"
GET /api/courses?filter[title]=like:%Laravel%

# Starts with "Introduction"
GET /api/courses?filter[title]=like:Introduction%

# Ends with "Basics"
GET /api/courses?filter[title]=like:%Basics
```

### In (Multiple Values)

Match any of multiple values:

```http
GET /api/courses?filter[level_tag]=in:beginner,intermediate
GET /api/courses?filter[status]=in:published,archived
```

### Between (Range)

Filter by range:

```http
# Date range
GET /api/courses?filter[created_at]=between:2025-01-01,2025-12-31

# Numeric range
GET /api/courses?filter[views_count]=between:100,1000
```

---

## Sort Syntax

### Single Field Sort

Ascending order (default):

```http
GET /api/courses?sort=title
GET /api/courses?sort=created_at
```

Descending order (prefix with `-`):

```http
GET /api/courses?sort=-created_at
GET /api/courses?sort=-views_count
```

### Default Sort

If no `sort` parameter is provided, each endpoint applies its default sort:

- **Courses:** `sort=title` (ascending by title)
- **Lessons:** `sort=order` (ascending by order)
- **Enrollments:** `sort=-created_at` (descending by creation date)
- **Categories:** `sort=-created_at` (descending by creation date)

---

## Common Use Cases

### 1. Get Published Courses

```http
GET /api/courses?filter[status]=published
```

### 2. Get Beginner Courses

```http
GET /api/courses?filter[level_tag]=beginner&filter[status]=published
```

### 3. Get Recent Enrollments

```http
GET /api/enrollments?sort=-enrolled_at&per_page=10
```

### 4. Get Active Enrollments for a User

```http
GET /api/enrollments?filter[user_id]=123&filter[status]=active
```

### 5. Get Courses by Category

```http
GET /api/courses?filter[category_id]=5&filter[status]=published
```

### 6. Get Popular Courses

```http
GET /api/courses?filter[status]=published&sort=-views_count&per_page=10
```

### 7. Get Courses Created This Year

```http
GET /api/courses?filter[created_at]=between:2025-01-01,2025-12-31
```

### 8. Search Courses by Title

```http
GET /api/courses?filter[title]=like:%Laravel%&filter[status]=published
```

### 9. Get Completed Enrollments

```http
GET /api/enrollments?filter[status]=completed&sort=-completed_at
```

### 10. Get Lessons by Content Type

```http
GET /api/lessons?filter[content_type]=video&filter[status]=published
```

---

## Advanced Filtering

### Multiple Filters

Combine multiple filters for precise results:

```http
GET /api/courses?filter[status]=published&filter[level_tag]=beginner&filter[type]=certification&filter[views_count]=gt:50
```

### Complex Date Filtering

Filter by date ranges:

```http
# Courses created in Q1 2025
GET /api/courses?filter[created_at]=between:2025-01-01,2025-03-31

# Enrollments completed after a specific date
GET /api/enrollments?filter[completed_at]=gte:2025-01-01&filter[status]=completed
```

### Combining In and Other Operators

```http
# Multiple statuses with additional filters
GET /api/courses?filter[status]=in:published,archived&filter[level_tag]=advanced&sort=-created_at
```

### Partial Text Search

```http
# Find courses with "Laravel" in title
GET /api/courses?filter[title]=like:%Laravel%&filter[status]=published

# Find categories starting with "Web"
GET /api/categories?filter[name]=like:Web%
```

---

## Best Practices

### 1. Always Filter by Status

For most resources, filter by status to get relevant results:

```http
GET /api/courses?filter[status]=published
```

### 2. Use Pagination

Always use pagination for large datasets:

```http
GET /api/courses?filter[status]=published&page=1&per_page=20
```

### 3. Sort for Consistency

Specify sort order for consistent results:

```http
GET /api/courses?filter[status]=published&sort=title
```

### 4. Combine Filters Efficiently

Use multiple filters to narrow down results:

```http
GET /api/courses?filter[status]=published&filter[level_tag]=beginner&filter[category_id]=5
```

### 5. Use Appropriate Operators

Choose the right operator for your use case:

- **Exact match:** Default or `eq:`
- **Partial search:** `like:`
- **Multiple values:** `in:`
- **Ranges:** `between:`, `gt:`, `lt:`

### 6. URL Encode Special Characters

When using special characters in filters, ensure proper URL encoding:

```http
# Correct
GET /api/courses?filter[title]=like:%Laravel%20Basics%

# Incorrect
GET /api/courses?filter[title]=like:%Laravel Basics%
```

---

## Error Handling

### Invalid Filter Field

If you use a filter field that's not allowed:

**Request:**
```http
GET /api/courses?filter[invalid_field]=value
```

**Response:**
```json
{
  "success": false,
  "message": "Invalid filter fields: invalid_field. Allowed filters: status, level_tag, type, category_id",
  "data": null,
  "meta": null,
  "errors": {
    "filter": ["invalid_field"]
  }
}
```

**Status Code:** `400 Bad Request`

### Invalid Sort Field

If you use a sort field that's not allowed:

**Request:**
```http
GET /api/courses?sort=invalid_field
```

**Response:**
```json
{
  "success": false,
  "message": "Invalid sort field: invalid_field. Allowed sorts: id, code, title, created_at, updated_at, published_at",
  "data": null,
  "meta": null,
  "errors": {
    "sort": ["invalid_field"]
  }
}
```

**Status Code:** `400 Bad Request`

### Invalid Operator

If you use an unsupported operator:

**Request:**
```http
GET /api/courses?filter[status]=invalid_op:value
```

**Response:**
```json
{
  "success": false,
  "message": "Invalid filter operator: invalid_op. Supported operators: eq, neq, gt, gte, lt, lte, like, in, between",
  "data": null,
  "meta": null,
  "errors": {
    "filter": ["Invalid operator: invalid_op"]
  }
}
```

**Status Code:** `400 Bad Request`

---

## Full-Text Search

Some endpoints support full-text search using the `search` parameter:

```http
GET /api/courses?search=Laravel&filter[status]=published
```

This searches across multiple fields (typically title, description, content) and returns relevant results.

**Note:** Not all endpoints support full-text search. Check the endpoint documentation for availability.

---

## Response Format

All filtered and sorted responses follow this format:

```json
{
  "success": true,
  "message": "Berhasil",
  "data": [
    {
      "id": 1,
      "title": "Laravel Basics",
      "status": "published",
      "level_tag": "beginner"
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 75,
      "from": 1,
      "to": 15
    }
  }
}
```

---

## Examples by Module

### Courses

```http
# Get all published beginner courses
GET /api/courses?filter[status]=published&filter[level_tag]=beginner&sort=title

# Get popular courses
GET /api/courses?filter[status]=published&sort=-views_count&per_page=10

# Get courses by category
GET /api/courses?filter[category_id]=5&filter[status]=published&sort=title
```

### Enrollments

```http
# Get active enrollments for a user
GET /api/enrollments?filter[user_id]=123&filter[status]=active&sort=-enrolled_at

# Get completed enrollments
GET /api/enrollments?filter[status]=completed&sort=-completed_at

# Get enrollments for a course
GET /api/enrollments?filter[course_id]=10&sort=-enrolled_at
```

### Lessons

```http
# Get published video lessons
GET /api/lessons?filter[status]=published&filter[content_type]=video&sort=order

# Get lessons by unit
GET /api/lessons?filter[unit_id]=5&filter[status]=published&sort=order
```

### Categories

```http
# Get active categories
GET /api/categories?filter[status]=active&sort=name

# Search categories
GET /api/categories?filter[name]=like:%Web%&sort=name
```

---

## Tips and Tricks

### 1. Testing Filters

Use tools like Postman or curl to test filters:

```bash
curl -X GET "http://localhost:8000/api/courses?filter[status]=published&sort=-created_at" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Debugging

Enable debug mode to see detailed error messages:

```env
APP_DEBUG=true
```

### 3. Performance

- Use specific filters to reduce result set size
- Limit `per_page` to reasonable values (15-50)
- Avoid using `like` with leading wildcards (`%value`) when possible

### 4. Caching

Consider caching frequently used filter combinations on the client side.

---

## Support

For questions or issues with filtering and sorting:

1. Check the [API Filters and Sorts Documentation](./API_FILTERS_AND_SORTS.md)
2. Review endpoint-specific documentation
3. Contact the development team

---

**Last Updated:** December 11, 2025
