# Schemes Module API Documentation

This module handles course management, units, lessons, blocks, tags, and user progression.

## Base URL
`/api/v1`

---

## 1. Courses

### List Courses
`GET /courses`

List all courses with filtering, sorting, and pagination.

**Query Parameters:**
- `per_page` (int, default: 15): Number of items per page.
- `filter[status]` (string): Filter by status (`draft`, `published`, `archived`).
- `filter[level_tag]` (string): Filter by level (`Beginner`, `Intermediate`, `Advanced`).
- `filter[type]` (string): Filter by type (`scheme`, `non_scheme`).
- `filter[category_id]` (int): Filter by category ID.
- `filter[search]` or `search` (string): Search in title and description (via Meilisearch).
- `filter[tag]` (string/array): Filter by tag name or slug.
- `include` (string, comma-separated): Include relations (`tags`, `category`, `instructor`, `units`).
- `sort` (string, comma-separated): Sort fields (`id`, `code`, `title`, `created_at`, `updated_at`, `published_at`).

### Show Course
`GET /courses/{slug}`

Get detailed information about a specific course.

---

### Create Course (Admin)
`POST /courses`

**Role:** `Superadmin`, `Admin`

**Body (Multipart Form Data):**
- `code` (string, required): Unique course code.
- `title` (string, required): Course title.
- `short_desc` (string, optional): Short description.
- `level_tag` (string, required): `Beginner`, `Intermediate`, `Advanced`.
- `type` (string, required): `scheme`, `non_scheme`.
- `enrollment_type` (string, required): `auto_accept`, `key_based`, `manual_approval`.
- `enrollment_key` (string, required if `key_based` and new): Key for enrollment.
- `progression_mode` (string, required): `free`, `sequential`.
- `category_id` (int, required): Category ID.
- `tags` (array, optional): Array of tag names/IDs.
- `outcomes` (array, optional): Array of learning outcomes.
- `prereq` (string, optional): Prerequisite text.
- `thumbnail` (file, optional): Image file (jpg, jpeg, png, webp, max 4MB).
- `banner` (file, optional): Image file (jpg, jpeg, png, webp, max 6MB).
- `course_admins` (array, optional): Array of User IDs.

### Update Course (Admin)
`PUT /courses/{slug}`

**Role:** `Superadmin`, `Admin`
**Body (JSON or Form Data):** Same as Create Course, but all fields are optional.

### Delete Course (Admin)
`DELETE /courses/{slug}`

---

### Publish/Unpublish Course (Admin)
`PUT /courses/{slug}/publish`
`PUT /courses/{slug}/unpublish`

### Enrollment Key Management (Admin)
- `POST /courses/{slug}/enrollment-key/generate`: Generate a new key.
- `PUT /courses/{slug}/enrollment-key`: Update key manually (body: `enrollment_key`).
- `DELETE /courses/{slug}/enrollment-key`: Remove key (reverts to `auto_accept`).

---

## 2. Units (Within a Course)

### List Units
`GET /courses/{slug}/units`

**Query Parameters:**
- `filter[status]` (string): `draft`, `published`.
- `include` (string): `course`, `lessons`.
- `sort` (string): `order`, `title`, `created_at`.

### Show Unit
`GET /courses/{slug}/units/{unit_slug}`

---

### Create Unit (Admin)
`POST /courses/{slug}/units`

**Body:**
- `code` (string, required): Unique code.
- `title` (string, required).
- `description` (string, optional).
- `order` (int, optional).

### Update Unit (Admin)
`PUT /courses/{slug}/units/{unit_slug}`

### Delete Unit (Admin)
`DELETE /courses/{slug}/units/{unit_slug}`

### Reorder Units (Admin)
`PUT /courses/{slug}/units/reorder`
**Body:** `{"units": [{"id": 1, "order": 1}, {"id": 2, "order": 2}]}`

### Publish/Unpublish Unit (Admin)
`PUT /courses/{slug}/units/{unit_slug}/publish`
`PUT /courses/{slug}/units/{unit_slug}/unpublish`

---

## 3. Lessons (Within a Unit)

### List Lessons
`GET /courses/{slug}/units/{unit_slug}/lessons`

**Query Parameters:**
- `filter[type]` (string).
- `filter[status]` (string).
- `include` (string): `unit`, `blocks`, `assignments`.

### Show Lesson
`GET /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}`

---

### Create Lesson (Admin)
`POST /courses/{slug}/units/{unit_slug}/lessons`

**Body:**
- `slug` (string, optional).
- `title` (string, required).
- `description` (string, optional).
- `markdown_content` (string, optional).
- `duration_minutes` (int, optional).
- `order` (int, optional).

### Update Lesson (Admin)
`PUT /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}`

### Delete Lesson (Admin)
`DELETE /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}`

### Publish/Unpublish Lesson (Admin)
`PUT /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}/publish`
`PUT /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}/unpublish`

---

## 4. Lesson Blocks (Content)

### List Blocks
`GET /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks`

### Show Block
`GET /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_id}`

---

### Create Block (Admin)
`POST /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks`

**Body (Multipart Form Data):**
- `type` (string, required): `text`, `video`, `image`, `file`.
- `content` (string, optional): Text content or description.
- `media` (file, required for non-text): The media file.
- `order` (int, optional).

### Update Block (Admin)
`PUT /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_id}`

**Body (Multipart or JSON):** Same as Create, but optional.

### Delete Block (Admin)
`DELETE /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_id}`

---

## 5. Progress Tracking

### Get Course Progress
`GET /courses/{slug}/progress`

Returns hierarchical progress for the course, units, and lessons.
**Query Params:** `user_id` (Admin only, to view others).

### Complete Lesson
`POST /courses/{slug}/units/{unit_slug}/lessons/{lesson_slug}/complete`

Marks a lesson as completed for the authenticated student.

---

## 6. Tags

### List Tags
`GET /tags`

**Query Parameters:** 
- `filter[search]` or `search` (string).
- `filter[name]`, `filter[slug]`, `filter[description]` (partial match).
- `per_page` (int).

### Show Tag
`GET /tags/{slug}`

---

### Create Tag (Admin)
`POST /tags`
**Body:** `{"name": "Tag Name"}`

### Update Tag (Admin)
`PUT /tags/{slug}`
**Body:** `{"name": "Updated Name"}`

### Delete Tag (Admin)
`DELETE /tags/{slug}`
