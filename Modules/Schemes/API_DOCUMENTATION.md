# API Documentation - Modules/Schemes

Dokumentasi lengkap endpoint API untuk modul **Schemes** (Course, Unit, Lesson, Lesson Block, Progress, dan Tag).
Semua endpoint berada di bawah prefix `/api/v1`.

---

## 1. Courses (Skema Pelatihan)

### List Courses
Mendapatkan daftar skema pelatihan dengan dukungan filter, pencarian, dan sorting.
- **Method**: `GET`
- **URL**: `/api/v1/courses`
- **Roles**: Public (Guest/Authenticated)
- **Query Params**:
  - `search`: String (Cari di judul/konten via Meilisearch)
  - `filter[status]`: `draft`, `published`, `archived`
  - `filter[level_tag]`: `dasar`, `menengah`, `mahir`
  - `filter[type]`: `okupasi`, `kluster`
  - `filter[category_id]`: Integer (ID Kategori)
  - `filter[tag]`: String/Array (Slug atau Nama Tag)
  - `sort`: `id`, `code`, `title`, `created_at`, `updated_at`, `published_at` (Gunakan prefix `-` untuk DESC)
  - `include`: `tags`, `category`, `instructor`, `units`

### Create Course
Membuat skema pelatihan baru. Gunakan `multipart/form-data`.
- **Method**: `POST`
- **URL**: `/api/v1/courses`
- **Roles**: `Superadmin`, `Admin`
- **Form Data**:
  - `code`: String (Wajib, Unik, maks 50)
  - `title`: String (Wajib, maks 255)
  - `short_desc`: String (Opsional)
  - `level_tag`: `dasar`, `menengah`, `mahir` (Wajib)
  - `type`: `okupasi`, `kluster` (Wajib)
  - `enrollment_type`: `auto_accept`, `key_based`, `approval` (Wajib)
  - `enrollment_key`: String (Wajib jika `key_based`, maks 100)
  - `progression_mode`: `sequential`, `free` (Wajib)
  - `category_id`: Integer (Wajib, ID Kategori)
  - `tags[]`: Array (Nama tag, contoh: `['Web', 'Laravel']`)
  - `outcomes[]`: Array of strings
  - `prereq`: String (Prasyarat)
  - `thumbnail`: File Image (Maks 4MB)
  - `banner`: File Image (Maks 6MB)
  - `instructor_id`: User ID (Opsional)
  - `course_admins[]`: Array of User IDs (Opsional)

### Update Course
Memperbarui skema. Gunakan `_method=PUT` jika mengirim file lewat `multipart/form-data`.
- **Method**: `PUT/PATCH`
- **URL**: `/api/v1/courses/{course_slug}`
- **Roles**: `Superadmin`, `Admin` (Owner/Instructor)

### Delete Course
- **Method**: `DELETE`
- **URL**: `/api/v1/courses/{course_slug}`

### Publish/Unpublish Skema
- **Publish**: `PUT /api/v1/courses/{course_slug}/publish` (Syarat: Min 1 unit & 1 lesson per unit)
- **Unpublish**: `PUT /api/v1/courses/{course_slug}/unpublish`

### Enrollment Key
- **Generate**: `POST /api/v1/courses/{course_slug}/enrollment-key/generate` (Kembalikan key acak 12 digit)
- **Update**: `PUT /api/v1/courses/{course_slug}/enrollment-key` (Body: `{ enrollment_key: "NEWKEY" }`)
- **Remove**: `DELETE /api/v1/courses/{course_slug}/enrollment-key` (Set ke `auto_accept`)

---

## 2. Units (Unit Kompetensi)

### List Units
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/units`
- **Include**: `course`, `lessons`

### Manage Unit (Admin Only)
- **Store**: `POST /api/v1/courses/{course_slug}/units`
  - Body: `code` (Req), `title` (Req), `description`, `order`, `status`
- **Show**: `GET /api/v1/courses/{course_slug}/units/{unit_slug}`
- **Update**: `PUT /api/v1/courses/{course_slug}/units/{unit_slug}`
- **Destroy**: `DELETE /api/v1/courses/{course_slug}/units/{unit_slug}`
- **Publish/Unpublish**: `PUT .../units/{unit_slug}/publish` atau `.../unpublish`
- **Reorder**: `PUT /api/v1/courses/{course_slug}/units/reorder`
  - Body: `{ "units": [ { "id": 1, "order": 1 }, { "id": 2, "order": 2 } ] }`

---

## 3. Lessons (Materi / Elemen)

### List & Show
- **Index**: `GET /api/v1/courses/{course_slug}/units/{unit_slug}/lessons`
- **Show**: `GET /api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}` (Akan mengecek aksesibilitas berdasarkan progresi jika user adalah Student)

### Manage Lesson (Admin Only)
- **Store**: `POST /api/v1/courses/{course_slug}/units/{unit_slug}/lessons`
  - Body: `title` (Req), `slug`, `description`, `markdown_content`, `order`, `duration_minutes`, `status`
- **Update**: `PUT /api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}`
- **Destroy**: `DELETE /api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}`
- **Publish/Unpublish**: `PUT .../lessons/{lesson_slug}/publish` atau `.../unpublish`

---

## 4. Lesson Blocks (Konten Materi)

### List & Show
- **Index**: `GET /api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks`
- **Show**: `GET .../blocks/{block_slug}`

### Manage Block (Admin Only)
- **Store**: `POST /api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks`
  - **Form Data**:
    - `type`: `text`, `video`, `image`, `file` (Wajib)
    - `content`: String (Wajib jika `text`)
    - `media`: File (Wajib jika `video/image/file`, maks 50MB)
    - `order`: Integer
- **Update**: `PUT .../blocks/{block_slug}` (Mendukung ganti tipe dan ganti media)
- **Destroy**: `DELETE .../blocks/{block_slug}`

---

## 5. Progress Tracking (Progres Belajar)

### View Progress
Melihat statistik progres user (berapa % selesai, daftar lesson yang sudah/belum).
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/progress`
- **Params**: `user_id` (Opsional bagi Admin untuk cek progres orang lain).

### Complete Lesson
Menandai suatu materi telah selesai dipelajari.
- **Method**: `POST`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/complete`
- **Effect**: Mengupdate status enrollment dan mencatat timestamp penyelesaian.

---

## 6. Tags

### Manage Tags
- **Index**: `GET /api/v1/tags` (Filter: `filter[name]`, `filter[slug]`)
- **Show**: `GET /api/v1/tags/{tag_slug}`
- **Store**: `POST /api/v1/tags`
  - Body: `{ "name": "Laravel" }` atau `{ "names": ["PHP", "Backend"] }`
- **Update**: `PUT /api/v1/tags/{tag_slug}`
  - Body: `{ "name": "Laravel Core" }`
- **Destroy**: `DELETE /api/v1/tags/{tag_slug}`
