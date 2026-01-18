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
  - `per_page`: Integer (Default: `15`)

### Show Course
Mendapatkan detail skema pelatihan beserta relasi.
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}`
- **Roles**: Public (Guest/Authenticated)
- **Response**: Course dengan relasi `tags`, `category`, `instructor`, `admins`, `units`

### Create Course
Membuat skema pelatihan baru. Gunakan `multipart/form-data`.
- **Method**: `POST`
- **URL**: `/api/v1/courses`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `create` pada Course
- **Form Data**:
  - `code`: String (Wajib, Unik, maks 50 karakter)
  - `slug`: String (Opsional, Unik, maks 100 karakter, auto-generated dari title jika kosong)
  - `title`: String (Wajib, maks 255 karakter)
  - `short_desc`: String (Opsional)
  - `level_tag`: Enum (Wajib) - `dasar`, `menengah`, `mahir`
  - `type`: Enum (Wajib) - `okupasi`, `kluster`
  - `enrollment_type`: Enum (Wajib) - `auto_accept`, `key_based`, `approval`
  - `enrollment_key`: String (Wajib jika `enrollment_type=key_based` saat create, maks 100 karakter)
  - `progression_mode`: Enum (Wajib) - `sequential`, `free`
  - `category_id`: Integer (Wajib saat create, exists:categories,id)
  - `tags`: Array/JSON String (Opsional, array of strings, contoh: `["Web", "Laravel"]`)
  - `outcomes`: Array/JSON String (Opsional, array of strings untuk learning outcomes)
  - `prereq`: String (Opsional, prasyarat course)
  - `thumbnail`: File Image (Opsional, jpg/jpeg/png/webp, maks 4MB)
  - `banner`: File Image (Opsional, jpg/jpeg/png/webp, maks 6MB)
  - `status`: Enum (Opsional, default: `draft`) - `draft`, `published`, `archived`
  - `instructor_id`: Integer (Opsional, exists:users,id)
  - `course_admins`: Array/JSON String (Opsional, array of integers, exists:users,id)

**Catatan**:
- Field `tags`, `outcomes`, dan `course_admins` dapat dikirim sebagai JSON string atau array
- Jika dikirim sebagai string, akan di-decode otomatis
- Slug akan di-generate otomatis dari `title` jika tidak disediakan

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
- `thumbnail.image`: "Thumbnail harus berupa gambar."
- `banner.image`: "Banner harus berupa gambar."
- `instructor_id.exists`: "Instruktur tidak ditemukan."
- `course_admins.*.exists`: "Admin course tidak ditemukan."

### Update Course
Memperbarui skema. Gunakan `_method=PUT` jika mengirim file lewat `multipart/form-data`.
- **Method**: `PUT/PATCH`
- **URL**: `/api/v1/courses/{course_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada course
- **Form Data**: Sama seperti Create Course, tetapi:
  - `category_id` tidak wajib saat update
  - `enrollment_key` tidak wajib meskipun `enrollment_type=key_based` saat update

### Delete Course
Menghapus skema pelatihan (soft delete).
- **Method**: `DELETE`
- **URL**: `/api/v1/courses/{course_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `delete` pada course

### Publish Course
Mempublikasikan skema pelatihan.
- **Method**: `PUT`
- **URL**: `/api/v1/courses/{course_slug}/publish`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada course
- **Syarat**: Minimal 1 unit dan 1 lesson per unit

### Unpublish Course
Membatalkan publikasi skema pelatihan.
- **Method**: `PUT`
- **URL**: `/api/v1/courses/{course_slug}/unpublish`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada course

### Generate Enrollment Key
Generate enrollment key acak dan set `enrollment_type` ke `key_based`.
- **Method**: `POST`
- **URL**: `/api/v1/courses/{course_slug}/enrollment-key/generate`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada course
- **Response**: Course resource dengan `enrollment_key` yang baru di-generate

### Update Enrollment Settings
Update enrollment type dan/atau enrollment key.
- **Method**: `PUT`
- **URL**: `/api/v1/courses/{course_slug}/enrollment-key`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada course
- **Body (JSON)**:
  - `enrollment_type`: Enum (Wajib) - `auto_accept`, `key_based`, `approval`
  - `enrollment_key`: String (Opsional, maks 100 karakter)

### Remove Enrollment Key
Hapus enrollment key dan set `enrollment_type` ke `auto_accept`.
- **Method**: `DELETE`
- **URL**: `/api/v1/courses/{course_slug}/enrollment-key`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada course

---

## 2. Units (Unit Kompetensi)

### List Units
Mendapatkan daftar unit dalam suatu course.
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/units`
- **Roles**: Authenticated
- **Authorization**: User harus memiliki akses view pada unit (enrolled atau admin)
- **Query Params**:
  - `filter`: Array (Filter untuk pencarian)
  - `per_page`: Integer (Default: `15`)
- **Response**: Paginated list of units

### Show Unit
Mendapatkan detail unit beserta lessons.
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}`
- **Roles**: Authenticated
- **Authorization**: User harus memiliki akses view pada unit
- **Response**: Unit dengan relasi `lessons`

### Create Unit
Membuat unit baru dalam course.
- **Method**: `POST`
- **URL**: `/api/v1/courses/{course_slug}/units`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada course
- **Body (JSON)**:
  - `code`: String (Wajib, Unik, maks 50 karakter)
  - `slug`: String (Opsional, Unik per course, maks 100 karakter, auto-generated dari title jika kosong)
  - `title`: String (Wajib, maks 255 karakter)
  - `description`: String (Opsional)
  - `order`: Integer (Opsional, min: 1, auto-increment jika kosong)
  - `status`: Enum (Opsional, default: `draft`) - `draft`, `published`

**Validation Messages**:
- `code.required`: "Kode wajib diisi."
- `code.unique`: "Kode sudah digunakan."
- `slug.unique`: "Slug sudah digunakan di course ini."
- `title.required`: "Judul wajib diisi."
- `order.min`: "Order minimal 1."

### Update Unit
Memperbarui unit.
- **Method**: `PUT/PATCH`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada unit
- **Body**: Sama seperti Create Unit

### Delete Unit
Menghapus unit.
- **Method**: `DELETE`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `delete` pada unit

### Publish Unit
Mempublikasikan unit.
- **Method**: `PUT`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/publish`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada unit

### Unpublish Unit
Membatalkan publikasi unit.
- **Method**: `PUT`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/unpublish`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada unit

### Reorder Units
Mengubah urutan units dalam course.
- **Method**: `PUT`
- **URL**: `/api/v1/courses/{course_slug}/units/reorder`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada course
- **Body (JSON)**:
  - `units`: Array (Wajib, array of integers, exists:units,id)
  - `units.*`: Integer (Wajib, ID unit)

**Contoh Body**:
```json
{
  "units": [3, 1, 2]
}
```

**Validation Messages**:
- `units.required`: "Daftar units wajib diisi."
- `units.array`: "Units harus berupa array."
- `units.*.required`: "Setiap item units wajib diisi."
- `units.*.integer`: "Setiap item units harus berupa ID (angka)."
- `units.*.exists`: "Unit tidak ditemukan."

---

## 3. Lessons (Materi / Elemen)

### List Lessons
Mendapatkan daftar lessons dalam suatu unit.
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons`
- **Roles**: Authenticated
- **Authorization**: User harus memiliki akses view pada unit
- **Query Params**:
  - `filter`: Array (Filter untuk pencarian)
  - `per_page`: Integer (Default: `15`)

### Show Lesson
Mendapatkan detail lesson. Akan mengecek aksesibilitas berdasarkan progression mode jika user adalah Student.
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}`
- **Roles**: Authenticated
- **Authorization**: User harus memiliki akses view pada lesson
- **Response**: Lesson resource dengan informasi aksesibilitas

### Create Lesson
Membuat lesson baru dalam unit.
- **Method**: `POST`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada unit
- **Body (JSON)**:
  - `slug`: String (Opsional, Unik per unit, maks 100 karakter, auto-generated dari title jika kosong)
  - `title`: String (Wajib, maks 255 karakter)
  - `description`: String (Opsional)
  - `markdown_content`: String (Opsional, HTML tags akan di-strip otomatis)
  - `order`: Integer (Opsional, min: 1, auto-increment jika kosong)
  - `duration_minutes`: Integer (Opsional, min: 0, default: `0`)
  - `status`: Enum (Opsional, default: `draft`) - `draft`, `published`

**Validation Messages**:
- `slug.unique`: "Slug sudah digunakan di unit ini."
- `title.required`: "Judul wajib diisi."
- `markdown_content.string`: "Markdown content harus berupa teks."
- `order.min`: "Order minimal 1."
- `duration_minutes.min`: "Durasi minimal 0."

### Update Lesson
Memperbarui lesson.
- **Method**: `PUT/PATCH`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada lesson
- **Body**: Sama seperti Create Lesson

### Delete Lesson
Menghapus lesson.
- **Method**: `DELETE`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `delete` pada lesson

### Publish Lesson
Mempublikasikan lesson.
- **Method**: `PUT`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/publish`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada lesson

### Unpublish Lesson
Membatalkan publikasi lesson.
- **Method**: `PUT`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/unpublish`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada lesson

---

## 4. Lesson Blocks (Konten Materi)

### List Lesson Blocks
Mendapatkan daftar blocks dalam suatu lesson.
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks`
- **Roles**: Authenticated
- **Authorization**: User harus memiliki akses view pada lesson
- **Query Params**:
  - `filter`: Array (Filter untuk pencarian)

### Show Lesson Block
Mendapatkan detail lesson block.
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_slug}`
- **Roles**: Authenticated
- **Authorization**: User harus memiliki akses view pada block

### Create Lesson Block
Membuat block baru dalam lesson. Gunakan `multipart/form-data`.
- **Method**: `POST`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada lesson
- **Form Data**:
  - `type`: Enum (Wajib) - `text`, `video`, `image`, `file`
  - `content`: String (Opsional, wajib jika `type=text`)
  - `order`: Integer (Opsional, min: 1, auto-increment jika kosong)
  - `media`: File (Conditional, wajib jika `type=video/image/file`, maks 50MB configurable via `app.lesson_block_max_upload_mb`)

**Validation Rules untuk `media`**:
- Wajib diisi jika `type` adalah `video`, `image`, atau `file`
- Untuk `type=image`: file harus memiliki MIME type `image/*`
- Untuk `type=video`: file harus memiliki MIME type `video/*`
- Untuk `type=file`: semua tipe file diperbolehkan

**Validation Messages**:
- `type.required`: "Field type wajib diisi."
- `type.in`: "Field type hanya boleh: text, video, image, file."
- `content.string`: "Field content harus berupa teks."
- `order.min`: "Field order minimal bernilai 1."
- `media.file`: "Field media harus berupa file yang valid."
- `media.max`: "Ukuran file media melebihi batas yang diizinkan."
- Custom: "File media wajib diunggah untuk tipe ini." (jika type=video/image/file tapi media kosong)
- Custom: "Tipe file tidak sesuai dengan tipe block." (jika MIME type tidak sesuai)

### Update Lesson Block
Memperbarui block. Mendukung ganti tipe dan ganti media.
- **Method**: `PUT/PATCH`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada block
- **Form Data**: Sama seperti Create Lesson Block

### Delete Lesson Block
Menghapus lesson block.
- **Method**: `DELETE`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/blocks/{block_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `delete` pada block

---

## 5. Progress Tracking (Progres Belajar)

### View Progress
Melihat statistik progres user (berapa % selesai, daftar lesson yang sudah/belum).
- **Method**: `GET`
- **URL**: `/api/v1/courses/{course_slug}/progress`
- **Roles**: Authenticated
- **Authorization**: 
  - User dapat melihat progress sendiri
  - Admin/Superadmin dapat melihat progress user lain dengan query parameter `user_id`
- **Query Params**:
  - `user_id`: Integer (Opsional, default: current user ID, hanya untuk admin untuk cek progres orang lain)

### Complete Lesson
Menandai suatu materi telah selesai dipelajari.
- **Method**: `POST`
- **URL**: `/api/v1/courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/complete`
- **Roles**: Authenticated
- **Authorization**: User harus enrolled di course dan memiliki akses ke lesson
- **Effect**: Mengupdate status enrollment dan mencatat timestamp penyelesaian
- **Response**: Progress resource dengan data terbaru setelah lesson ditandai selesai

---

## 6. Tags

### List Tags
Mendapatkan daftar tags.
- **Method**: `GET`
- **URL**: `/api/v1/tags`
- **Roles**: Public (Guest/Authenticated)
- **Query Params**:
  - `filter[name]`: String (Filter berdasarkan nama)
  - `filter[slug]`: String (Filter berdasarkan slug)
  - `per_page`: Integer (Default: `15`)

### Show Tag
Mendapatkan detail tag.
- **Method**: `GET`
- **URL**: `/api/v1/tags/{tag_slug}`
- **Roles**: Public (Guest/Authenticated)

### Create Tag
Membuat tag baru. Mendukung single atau bulk create.
- **Method**: `POST`
- **URL**: `/api/v1/tags`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `create` pada Tag
- **Body (JSON)**:
  - `name`: String (Conditional, required_without:names, min: 1, maks 100 karakter, unique)
  - `names`: Array (Conditional, required_without:name, array of strings, min: 1 item)
  - `names.*`: String (min: 1, maks 100 karakter)

**Contoh Single Create**:
```json
{
  "name": "Laravel"
}
```

**Contoh Bulk Create**:
```json
{
  "names": ["PHP", "Backend", "Web Development"]
}
```

### Update Tag
Memperbarui tag.
- **Method**: `PUT/PATCH`
- **URL**: `/api/v1/tags/{tag_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `update` pada tag
- **Body (JSON)**:
  - `name`: String (Wajib, min: 1, maks 100 karakter, unique kecuali tag saat ini)

### Delete Tag
Menghapus tag.
- **Method**: `DELETE`
- **URL**: `/api/v1/tags/{tag_slug}`
- **Roles**: `Superadmin`, `Admin`
- **Authorization**: Permission `delete` pada tag

---

## 📝 Catatan Umum

### Authentication
- Endpoint yang memerlukan autentikasi menggunakan `auth:api` middleware
- Token harus dikirim via header: `Authorization: Bearer {token}`

### Authorization
- **Role-based**: `Superadmin` dan `Admin` memiliki akses penuh untuk management
- **Permission-based**: Menggunakan Laravel Policy untuk granular access control
- **Hierarchy validation**: Semua nested resources (Unit, Lesson, Block) memvalidasi hierarki parent-child

### Pagination
- Default: 15 items per page
- Dapat dikustomisasi via query parameter `per_page`
- Response format mengikuti Laravel pagination standard

### Slug Generation
- Slug di-generate otomatis dari `title` atau `name` jika tidak disediakan
- Slug harus unique dalam scope tertentu (course, unit per course, lesson per unit)

### File Upload
- **Course**: `thumbnail` (max 4MB), `banner` (max 6MB)
- **Lesson Block**: `media` (max 50MB, configurable via `app.lesson_block_max_upload_mb`)
- **Supported image formats**: jpg, jpeg, png, webp
- **Video dan file**: semua format dengan validasi MIME type

### Enum Values

**LevelTag** (level_tag):
- `dasar` - Tingkat Dasar
- `menengah` - Tingkat Menengah
- `mahir` - Tingkat Mahir

**CourseType** (type):
- `okupasi` - Skema Okupasi
- `kluster` - Skema Kluster

**EnrollmentType** (enrollment_type):
- `auto_accept` - Otomatis menerima semua enrollment
- `key_based` - Memerlukan enrollment key
- `approval` - Memerlukan approval manual

**ProgressionMode** (progression_mode):
- `sequential` - Harus menyelesaikan lesson secara berurutan
- `free` - Dapat mengakses lesson mana saja

**CourseStatus** (status untuk Course):
- `draft` - Draft (belum dipublikasikan)
- `published` - Published (sudah dipublikasikan)
- `archived` - Archived (diarsipkan)

**CourseStatus** (status untuk Unit/Lesson):
- `draft` - Draft (belum dipublikasikan)
- `published` - Published (sudah dipublikasikan)

**LessonBlockType** (type):
- `text` - Konten text/markdown
- `video` - Video file
- `image` - Image file
- `file` - File attachment lainnya
