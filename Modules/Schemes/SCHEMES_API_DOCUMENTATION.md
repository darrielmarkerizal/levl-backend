# Dokumentasi API - Modul Schemes

Dokumentasi lengkap untuk semua endpoint API yang tersedia di modul Schemes.

**Base URL**: `/api/v1`

---

## 📚 Daftar Isi

- [Course API](#course-api)
- [Unit API](#unit-api)
- [Lesson API](#lesson-api)
- [Lesson Block API](#lesson-block-api)
- [Progress API](#progress-api)
- [Tag API](#tag-api)

---

## Course API

### 1. List Courses (Public)

**Endpoint**: `GET /courses`

**Authentication**: Tidak diperlukan

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | `15` | Jumlah item per halaman |
| `filter` | array | No | `[]` | Filter untuk pencarian |

**Response**: Paginated list of courses

---

### 2. Get Course Details (Public)

**Endpoint**: `GET /courses/{slug}`

**Authentication**: Tidak diperlukan

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Course slug |

**Response**: Course detail dengan relasi tags, category, instructor, admins, dan units

---

### 3. Create Course

**Endpoint**: `POST /courses`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `create` pada Course

**Request Body** (multipart/form-data):

| Field              | Type              | Required              | Validation                             | Default        | Description                                                          |
| ------------------ | ----------------- | --------------------- | -------------------------------------- | -------------- | -------------------------------------------------------------------- |
| `code`             | string            | **Yes**               | max:50, unique                         | -              | Kode course                                                          |
| `slug`             | string            | No                    | max:100, unique                        | Auto-generated | Slug course                                                          |
| `title`            | string            | **Yes**               | max:255                                | -              | Judul course                                                         |
| `short_desc`       | string            | No                    | -                                      | `null`         | Deskripsi singkat                                                    |
| `level_tag`        | enum              | **Yes**               | `Beginner`, `Intermediate`, `Advanced` | -              | Level course                                                         |
| `type`             | enum              | **Yes**               | `Free`, `Premium`                      | -              | Tipe course                                                          |
| `enrollment_type`  | enum              | **Yes**               | `auto_accept`, `key_based`, `approval` | -              | Jenis enrollment                                                     |
| `enrollment_key`   | string            | Conditional\*         | max:100                                | `null`         | Kode enrollment (wajib jika `enrollment_type=key_based` saat create) |
| `progression_mode` | enum              | **Yes**               | `Linear`, `Flexible`                   | -              | Mode progres                                                         |
| `category_id`      | integer           | **Yes** (create only) | exists:categories,id                   | -              | ID kategori                                                          |
| `tags`             | array/JSON string | No                    | array of strings                       | `[]`           | Daftar tag                                                           |
| `tags.*`           | string            | No                    | -                                      | -              | Nama tag                                                             |
| `outcomes`         | array/JSON string | No                    | array of strings                       | `[]`           | Daftar learning outcomes                                             |
| `outcomes.*`       | string            | No                    | -                                      | -              | Outcome text                                                         |
| `prereq`           | string            | No                    | -                                      | `null`         | Prasyarat course                                                     |
| `thumbnail`        | file              | No                    | image (jpg,jpeg,png,webp), max:4MB     | `null`         | Thumbnail course                                                     |
| `banner`           | file              | No                    | image (jpg,jpeg,png,webp), max:6MB     | `null`         | Banner course                                                        |
| `status`           | enum              | No                    | `Draft`, `Published`, `Archived`       | `Draft`        | Status course                                                        |
| `instructor_id`    | integer           | No                    | exists:users,id                        | `null`         | ID instruktur                                                        |
| `course_admins`    | array/JSON string | No                    | array of integers                      | `[]`           | Daftar ID admin course                                               |
| `course_admins.*`  | integer           | No                    | exists:users,id                        | -              | User ID admin                                                        |

> **Note**: Field `tags`, `outcomes`, dan `course_admins` dapat dikirim sebagai JSON string atau array. Jika dikirim sebagai string, akan di-decode otomatis.

**Validation Messages**:

- `code.required`: "Kode wajib diisi."
- `code.unique`: "Kode sudah digunakan."
- `title.required`: "Judul wajib diisi."
- `level_tag.required`: "Level wajib diisi."
- `type.required`: "Tipe wajib diisi."
- `enrollment_type.required`: "Jenis enrollment wajib dipilih."
- `enrollment_key.required_if`: "Kode enrollment wajib diisi untuk mode key-based."
- `progression_mode.required`: "Mode progres wajib diisi."
- `category_id.required`: "Kategori wajib dipilih."
- `category_id.exists`: "Kategori tidak ditemukan."

---

### 4. Update Course

**Endpoint**: `PUT /courses/{slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Course slug |

**Request Body**: Sama seperti Create Course, tetapi:

- `category_id` tidak wajib saat update
- `enrollment_key` tidak wajib meskipun `enrollment_type=key_based` saat update

---

### 5. Delete Course

**Endpoint**: `DELETE /courses/{slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `delete` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Course slug |

---

### 6. Publish Course

**Endpoint**: `PUT /courses/{slug}/publish`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Course slug |

---

### 7. Unpublish Course

**Endpoint**: `PUT /courses/{slug}/unpublish`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Course slug |

---

### 8. Generate Enrollment Key

**Endpoint**: `POST /courses/{slug}/enrollment-key/generate`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Course slug |

**Effect**: Mengubah `enrollment_type` menjadi `key_based` dan generate enrollment key baru

---

### 9. Update Enrollment Settings

**Endpoint**: `PUT /courses/{slug}/enrollment-key`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Course slug |

**Request Body** (JSON):
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `enrollment_type` | enum | **Yes** | `auto_accept`, `key_based`, `approval` | Jenis enrollment |
| `enrollment_key` | string | No | max:100 | Kode enrollment custom |

---

### 10. Remove Enrollment Key

**Endpoint**: `DELETE /courses/{slug}/enrollment-key`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Course slug |

**Effect**: Mengubah `enrollment_type` menjadi `auto_accept`

---

## Unit API

### 1. List Units

**Endpoint**: `GET /courses/{course_slug}/units`

**Authentication**: Required (`auth:api`)

**Authorization**: User harus memiliki akses view pada unit (enrolled atau admin)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | `15` | Jumlah item per halaman |
| `filter` | array | No | `[]` | Filter untuk pencarian |

---

### 2. Get Unit Details

**Endpoint**: `GET /courses/{course_slug}/units/{unit_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: User harus memiliki akses view pada unit

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |

**Response**: Unit detail dengan relasi lessons

---

### 3. Create Unit

**Endpoint**: `POST /courses/{course_slug}/units`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |

**Request Body** (JSON):
| Field | Type | Required | Validation | Default | Description |
|-------|------|----------|------------|---------|-------------|
| `code` | string | **Yes** | max:50, unique | - | Kode unit |
| `slug` | string | No | max:100, unique per course | Auto-generated | Slug unit |
| `title` | string | **Yes** | max:255 | - | Judul unit |
| `description` | string | No | - | `null` | Deskripsi unit |
| `order` | integer | No | min:1 | Auto-increment | Urutan unit |
| `status` | enum | No | `Draft`, `Published` | `Draft` | Status unit |

**Validation Messages**:

- `code.required`: "Kode wajib diisi."
- `code.unique`: "Kode sudah digunakan."
- `slug.unique`: "Slug sudah digunakan di course ini."
- `title.required`: "Judul wajib diisi."
- `order.min`: "Order minimal 1."

---

### 4. Update Unit

**Endpoint**: `PUT /courses/{course_slug}/units/{unit_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada unit

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |

**Request Body**: Sama seperti Create Unit

---

### 5. Delete Unit

**Endpoint**: `DELETE /courses/{course_slug}/units/{unit_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `delete` pada unit

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |

---

### 6. Publish Unit

**Endpoint**: `PUT /courses/{course_slug}/units/{unit_slug}/publish`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada unit

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |

---

### 7. Unpublish Unit

**Endpoint**: `PUT /courses/{course_slug}/units/{unit_slug}/unpublish`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada unit

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |

---

### 8. Reorder Units

**Endpoint**: `PUT /courses/{course_slug}/units/reorder`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada course

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |

**Request Body** (JSON):
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `units` | array | **Yes** | array of integers | Daftar ID unit dalam urutan baru |
| `units.*` | integer | **Yes** | exists:units,id | Unit ID |

**Validation Messages**:

- `units.required`: "Daftar units wajib diisi."
- `units.array`: "Units harus berupa array."
- `units.*.required`: "Setiap item units wajib diisi."
- `units.*.integer`: "Setiap item units harus berupa ID (angka)."
- `units.*.exists`: "Unit tidak ditemukan."

---

## Lesson API

### 1. List Lessons

**Endpoint**: `GET /courses/{course_slug}/units/{unit_slug}/lessons`

**Authentication**: Required (`auth:api`)

**Authorization**: User harus memiliki akses view pada unit

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | `15` | Jumlah item per halaman |
| `filter` | array | No | `[]` | Filter untuk pencarian |

---

### 2. Get Lesson Details

**Endpoint**: `GET /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: User harus memiliki akses view pada lesson

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |

---

### 3. Create Lesson

**Endpoint**: `POST /courses/{course_slug}/units/{unit_slug}/lessons`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada unit

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |

**Request Body** (JSON):
| Field | Type | Required | Validation | Default | Description |
|-------|------|----------|------------|---------|-------------|
| `slug` | string | No | max:100, unique per unit | Auto-generated | Slug lesson |
| `title` | string | **Yes** | max:255 | - | Judul lesson |
| `description` | string | No | - | `null` | Deskripsi lesson |
| `markdown_content` | string | No | - | `null` | Konten markdown (HTML tags akan di-strip) |
| `order` | integer | No | min:1 | Auto-increment | Urutan lesson |
| `duration_minutes` | integer | No | min:0 | `0` | Durasi estimasi (menit) |
| `status` | enum | No | `Draft`, `Published` | `Draft` | Status lesson |

**Validation Messages**:

- `slug.unique`: "Slug sudah digunakan di unit ini."
- `title.required`: "Judul wajib diisi."
- `markdown_content.string`: "Markdown content harus berupa teks."
- `order.min`: "Order minimal 1."
- `duration_minutes.min`: "Durasi minimal 0."

---

### 4. Update Lesson

**Endpoint**: `PUT /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada lesson

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |

**Request Body**: Sama seperti Create Lesson

---

### 5. Delete Lesson

**Endpoint**: `DELETE /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `delete` pada lesson

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |

---

### 6. Publish Lesson

**Endpoint**: `PUT /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/publish`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada lesson

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |

---

### 7. Unpublish Lesson

**Endpoint**: `PUT /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/unpublish`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada lesson

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |

---

## Lesson Block API

### 1. List Lesson Blocks

**Endpoint**: `GET /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks`

**Authentication**: Required (`auth:api`)

**Authorization**: User harus memiliki akses view pada lesson

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `filter` | array | No | `[]` | Filter untuk pencarian |

---

### 2. Get Lesson Block Details

**Endpoint**: `GET /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: User harus memiliki akses view pada block

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |
| `block_slug` | string | Yes | Block slug |

---

### 3. Create Lesson Block

**Endpoint**: `POST /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada lesson

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |

**Request Body** (multipart/form-data):
| Field | Type | Required | Validation | Default | Description |
|-------|------|----------|------------|---------|-------------|
| `type` | enum | **Yes** | `text`, `video`, `image`, `file` | - | Tipe block |
| `content` | string | No | - | `null` | Konten text (untuk type=text) |
| `order` | integer | No | min:1 | Auto-increment | Urutan block |
| `media` | file | Conditional\* | max:50MB (configurable) | `null` | File media (wajib untuk type: video, image, file) |

**Validation Rules untuk `media`**:

- Wajib diisi jika `type` adalah `video`, `image`, atau `file`
- Untuk `type=image`: file harus memiliki MIME type `image/*`
- Untuk `type=video`: file harus memiliki MIME type `video/*`
- Untuk `type=file`: semua tipe file diperbolehkan
- Maksimal ukuran file: 50MB (dapat dikonfigurasi via `app.lesson_block_max_upload_mb`)

**Validation Messages**:

- `type.required`: "Field type wajib diisi."
- `type.in`: "Field type hanya boleh: text, video, image, file."
- `content.string`: "Field content harus berupa teks."
- `order.min`: "Field order minimal bernilai 1."
- `media.file`: "Field media harus berupa file yang valid."
- `media.max`: "Ukuran file media melebihi batas yang diizinkan."
- Custom: "File media wajib diunggah untuk tipe ini." (jika type=video/image/file tapi media kosong)
- Custom: "Tipe file tidak sesuai dengan tipe block." (jika MIME type tidak sesuai)

---

### 4. Update Lesson Block

**Endpoint**: `PUT /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada block

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |
| `block_slug` | string | Yes | Block slug |

**Request Body**: Sama seperti Create Lesson Block

---

### 5. Delete Lesson Block

**Endpoint**: `DELETE /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `delete` pada block

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |
| `block_slug` | string | Yes | Block slug |

---

## Progress API

### 1. Get Course Progress

**Endpoint**: `GET /courses/{course_slug}/progress`

**Authentication**: Required (`auth:api`)

**Authorization**:

- User dapat melihat progress sendiri
- Admin/Superadmin dapat melihat progress user lain dengan query parameter `user_id`

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `user_id` | integer | No | Current user ID | ID user yang ingin dilihat progressnya (hanya untuk admin) |

---

### 2. Complete Lesson

**Endpoint**: `POST /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/complete`

**Authentication**: Required (`auth:api`)

**Authorization**: User harus enrolled di course dan memiliki akses ke lesson

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `course_slug` | string | Yes | Course slug |
| `unit_slug` | string | Yes | Unit slug |
| `lesson_slug` | string | Yes | Lesson slug |

**Request Body**: Tidak ada

**Response**: Progress data terbaru setelah lesson ditandai selesai

---

## Tag API

### 1. List Tags (Public)

**Endpoint**: `GET /tags`

**Authentication**: Tidak diperlukan

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | `15` | Jumlah item per halaman |
| `filter` | array | No | `[]` | Filter untuk pencarian |

---

### 2. Get Tag Details (Public)

**Endpoint**: `GET /tags/{slug}`

**Authentication**: Tidak diperlukan

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Tag slug |

---

### 3. Create Tag

**Endpoint**: `POST /tags`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `create` pada Tag

**Request Body** (JSON):
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | Conditional* | min:1, max:100, unique | Nama tag tunggal |
| `names` | array | Conditional* | array of strings, min:1 | Daftar nama tag (bulk create) |
| `names.*` | string | No | min:1, max:100 | Nama tag |

> **Note**: Salah satu dari `name` atau `names` harus diisi. Gunakan `names` untuk membuat multiple tags sekaligus.

---

### 4. Update Tag

**Endpoint**: `PUT /tags/{slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `update` pada tag

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Tag slug |

**Request Body** (JSON):
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | **Yes** | min:1, max:100, unique (ignore current) | Nama tag baru |

---

### 5. Delete Tag

**Endpoint**: `DELETE /tags/{slug}`

**Authentication**: Required (`auth:api`)

**Authorization**: Role `Superadmin` atau `Admin`, dengan permission `delete` pada tag

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Tag slug |

---

## 📝 Catatan Umum

### Authentication

- Endpoint yang memerlukan autentikasi menggunakan `auth:api` middleware
- Token harus dikirim via header: `Authorization: Bearer {token}`

### Authorization

- Role-based: `Superadmin` dan `Admin` memiliki akses penuh untuk management
- Permission-based: Menggunakan Laravel Policy untuk granular access control
- Hierarchy validation: Semua nested resources (Unit, Lesson, Block) memvalidasi hierarki parent-child

### Pagination

- Default: 15 items per page
- Dapat dikustomisasi via query parameter `per_page`
- Response format mengikuti Laravel pagination standard

### Slug Generation

- Slug di-generate otomatis dari `title` atau `name` jika tidak disediakan
- Slug harus unique dalam scope tertentu (course, unit, lesson)

### File Upload

- Course: `thumbnail` (max 4MB), `banner` (max 6MB)
- Lesson Block: `media` (max 50MB, configurable)
- Supported image formats: jpg, jpeg, png, webp
- Video dan file: semua format dengan validasi MIME type

### Enum Values

**LevelTag**:

- `Beginner`
- `Intermediate`
- `Advanced`

**CourseType**:

- `Free`
- `Premium`

**EnrollmentType**:

- `auto_accept`: Otomatis menerima semua enrollment
- `key_based`: Memerlukan enrollment key
- `approval`: Memerlukan approval manual

**ProgressionMode**:

- `Linear`: Harus menyelesaikan lesson secara berurutan
- `Flexible`: Dapat mengakses lesson mana saja

**CourseStatus** (untuk Course):

- `Draft`
- `Published`
- `Archived`

**CourseStatus** (untuk Unit/Lesson):

- `Draft`
- `Published`

**LessonBlockType**:

- `text`: Konten text/markdown
- `video`: Video file
- `image`: Image file
- `file`: File attachment lainnya
