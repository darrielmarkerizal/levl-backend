# Dokumentasi API Forum

Dokumentasi ini menjelaskan endpoint API untuk modul Forum.

## Base URLs
- **Course Forum:** `/api/v1/courses/{course_slug}/forum`
- **Forum Dashboard:** `/api/v1/forums`

## Daftar Isi
- [Overview](#overview)
- [Forum Dashboard (Admin/Instructor)](#forum-dashboard-admininstructor)
- [Thread (Diskusi)](#thread-diskusi)
- [Reply (Balasan)](#reply-balasan)
- [Reaction (Reaksi)](#reaction-reaksi)
- [Statistik](#statistik)
- [Contoh Penggunaan](#contoh-penggunaan)

---

## Overview

### Forum Structure

Forum threads are exclusively attached to courses. Each course has its own forum where students and instructors can discuss course-related topics.

### User Mentions / Tagging
Anda dapat men-tag user lain di dalam konten thread maupun reply dengan menggunakan format `@username`.
- Sistem akan otomatis mendeteksi username yang valid dan menyimpan data mention.
- Ini berguna untuk menarik perhatian user tertentu atau memberi referensi.

### Search Username (Mention)
Gunakan endpoint berikut untuk pencarian username (mention) dengan scope course yang sama.

**Endpoint:** `GET /api/v1/courses/{course_slug}/users/mentions`

**Query Parameters:**
| Parameter | Tipe | Wajib | Deskripsi | Default |
| :--- | :--- | :--- | :--- | :--- |
| `search` | string | Ya | Keyword pencarian (username/nama) | - |
| `limit` | integer | Tidak | Batas hasil (1-20) | `10` |

**Contoh Request:**
```http
GET /api/v1/courses/web-development-course/users/mentions?search=jan&limit=10
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 10,
      "name": "Jane Smith",
      "username": "jane.smith",
      "avatar_url": "https://example.com/avatar.jpg"
    }
  ]
}
```

### Authentication
Semua endpoint membutuhkan:
- Header: `Authorization: Bearer {token}`
- Minimal role: `student`, `instructor`, atau `admin`
- User harus terdaftar/enroll dalam course

### Media & Attachments
Forum threads and replies support file attachments via Spatie MediaLibrary (configured for DigitalOcean Spaces).

**Supported Files:**
| Category | Formats | MIME Types |
|----------|---------|-----------|
| **Images** | JPG, JPEG, PNG, GIF | `image/jpeg`, `image/png`, `image/gif` |
| **Documents** | PDF | `application/pdf` |
| **Videos** | MP4, WebM, OGG, MOV, AVI | `video/mp4`, `video/webm`, `video/ogg`, `video/quicktime`, `video/x-msvideo` |

**Limits:**
- **Max files**: 5 per request
- **Max size**: 50MB per file
- **Total size**: 250MB per request

---

## Forum Dashboard (Admin/Instructor)

### 1. List All Threads (Admin/Instructor)
Menampilkan semua thread dari semua course yang dikelola user.

**Endpoint:** `GET /forums/threads`

**Authorization:** Admin, Superadmin, atau Instructor (hanya threads dari course mereka)

**Query Parameters:**
| Parameter | Tipe | Wajib | Allowed Values | Deskripsi | Default |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `page` | integer | Tidak | >= 1 | Halaman pagination | `1` |
| `per_page` | integer | Tidak | 1-100 | Jumlah item per halaman | `20` |
| `sort` | string | Tidak | `id`, `-id`, `created_at`, `-created_at`, `last_activity_at`, `-last_activity_at`, `views_count`, `-views_count`, `replies_count`, `-replies_count` | Sort field (gunakan `-` untuk descending) | `-last_activity_at` |
| `search` | string | Tidak | Any string | Kata kunci pencarian (via Meilisearch) | - |
| `filter[author_id]`| integer | Tidak | Valid user ID | Filter berdasarkan author | - |
| `filter[pinned]` | boolean | Tidak | `1`, `0`, `true`, `false` | Filter pinned threads | - |
| `filter[resolved]` | boolean | Tidak | `1`, `0`, `true`, `false` | Filter resolved threads | - |
| `filter[closed]` | boolean | Tidak | `1`, `0`, `true`, `false` | Filter closed threads | - |
| `filter[is_mentioned]` | boolean | Tidak | `1`, `0`, `true`, `false` | Filter thread dimana user di-tag | - |

**Contoh Request:**
```http
GET /api/v1/forums/threads?page=1&per_page=50&search=laravel
Authorization: Bearer {admin_token}
```

**Contoh Request (dengan sort dan filter):**
```http
GET /api/v1/forums/threads?sort=-created_at&per_page=20&filter[pinned]=1&filter[author_id]=10
Authorization: Bearer {instructor_token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Threads retrieved successfully.",
  "data": [
    {
      "id": 1,
      "title": "Bagaimana cara install Laravel?",
      "author": {
        "id": 10,
        "username": "jane.smith",
        "name": "Jane Smith",
        "email": "jane@example.com",
        "avatar": "https://example.com/avatar.jpg"
      },
      "forumable_type": "Modules\\Schemes\\Models\\Course",
      "forumable_slug": "web-development-course",
      "views_count": 150,
      "replies_count": 25,
      "is_pinned": true,
      "is_closed": false,
      "is_mentioned": false,
      "created_at": "2026-02-01T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 450,
    "last_page": 9
  }
}
```

---

### 2. My Threads
Menampilkan semua thread yang dibuat oleh user yang login.

**Endpoint:** `GET /forums/my-threads`

**Authorization:** Semua user (student, instructor, admin, superadmin)

**Query Parameters:**
| Parameter | Tipe | Wajib | Allowed Values | Deskripsi | Default |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `page` | integer | Tidak | >= 1 | Halaman pagination | `1` |
| `per_page` | integer | Tidak | 1-100 | Jumlah item per halaman | `20` |
| `sort` | string | Tidak | `id`, `-id`, `created_at`, `-created_at`, `last_activity_at`, `-last_activity_at`, `views_count`, `-views_count`, `replies_count`, `-replies_count` | Sort field | `-last_activity_at` |
| `search` | string | Tidak | Any string | Kata kunci pencarian | - |
| `filter[pinned]` | boolean | Tidak | `1`, `0`, `true`, `false` | Filter pinned threads | - |
| `filter[resolved]` | boolean | Tidak | `1`, `0`, `true`, `false` | Filter resolved threads | - |
| `filter[closed]` | boolean | Tidak | `1`, `0`, `true`, `false` | Filter closed threads | - |
| `filter[is_mentioned]` | boolean | Tidak | `1`, `0`, `true`, `false` | Filter thread dimana user di-tag | - |

**Contoh Request:**
```http
GET /api/v1/forums/my-threads?page=1&per_page=20&search=javascript
Authorization: Bearer {user_token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Your threads retrieved successfully.",
  "data": [
    {
      "id": 5,
      "title": "Diskusi: Setup Project Baru",
      "author": {
        "id": 17,
        "username": "john.doe",
        "name": "John Doe",
        "email": "john@example.com",
        "avatar": "https://example.com/avatar.jpg"
      },
      "forumable_slug": "web-development-course",
      "replies_count": 3,
      "views_count": 20,
      "is_pinned": false,
      "created_at": "2026-02-03T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 8,
    "last_page": 1
  }
}
```

---

### 3. Trending Threads (Admin/Instructor)
Menampilkan thread dengan aktivitas tertinggi (paling banyak replies/views).

**Endpoint:** `GET /forums/threads/trending`

**Authorization:** Admin, Superadmin, atau Instructor

**Query Parameters:**
| Parameter | Tipe | Wajib | Allowed Values | Deskripsi | Default |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `search` | string | Tidak | Any string | Kata kunci pencarian (via Meilisearch) | - |
| `page` | integer | Tidak | >= 1 | Halaman pagination | `1` |
| `filter[limit]` | integer | Tidak | 1-50 | Jumlah item per halaman | `10` |
| `filter[period]` | string | Tidak | `24hours`, `7days`, `30days`, `90days` | Periode aktivitas | `7days` |

**Validasi:**
- `filter[limit]`: `nullable|integer|min:1|max:50`
- `filter[period]`: `nullable|in:24hours,7days,30days,90days`

**Contoh Request (default 7 days):**
```http
GET /api/v1/forums/threads/trending?filter[limit]=10&filter[period]=7days&search=laravel&page=1
Authorization: Bearer {instructor_token}
```

**Contoh Request (last 24 hours, top 20):**
```http
GET /api/v1/forums/threads/trending?filter[period]=24hours&filter[limit]=20
Authorization: Bearer {admin_token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Trending threads retrieved successfully.",
  "data": [
    {
      "id": 1,
      "title": "Bagaimana cara install Laravel?",
      "author": {
        "id": 10,
        "username": "jane.smith",
        "name": "Jane Smith",
        "email": "jane@example.com",
        "avatar": "https://example.com/avatar.jpg"
      },
      "forumable_slug": "web-development-course",
      "replies_count": 25,
      "views_count": 150,
      "created_at": "2026-02-01T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

---

## Thread (Diskusi)

### 1. Menampilkan Daftar Thread
Menampilkan daftar diskusi dengan fitur pencarian, filter, dan pagination.

**Endpoint:** `GET /courses/{course_slug}/forum/threads`

**Query Parameters:**
| Parameter | Tipe | Wajib | Allowed Values | Deskripsi | Default |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `page` | integer | Tidak | >= 1 | Halaman pagination | `1` |
| `per_page` | integer | Tidak | 1-100 | Jumlah item per halaman | `20` |
| `include` | string | Tidak | `author`, `course`, `media`, `tags`, `replies`, `topLevelReplies`, `topLevelReplies.children` (recursive up to 10 levels), `topLevelReplies.author`, `topLevelReplies.media` | Comma-separated relationships to include. Gunakan `topLevelReplies` untuk struktur nested. | - |
| `sort` | string | Tidak | `created_at`, `-created_at`, `updated_at`, `-updated_at`, `views_count`, `-views_count`, `replies_count`, `-replies_count`, `last_activity_at`, `-last_activity_at`, `title`, `-title`, `pinned`, `-pinned` | Sort field (gunakan `-` untuk descending) | `-is_pinned, -last_activity_at` |
| `filter[title]` | string | Tidak | Any string | Filter berdasarkan judul (partial match) | - |
| `filter[content]` | string | Tidak | Any string | Filter berdasarkan konten (partial match) | - |
| `filter[author_id]`| integer | Tidak | Valid user ID | Filter berdasarkan ID pembuat thread | - |
| `filter[is_pinned]` | boolean | Tidak | `1`, `0` | Filter exact match pinned | - |
| `filter[pinned]` | boolean | Tidak | `1`, `0`, `true`, `false` | Scope filter pinned threads | - |
| `filter[closed]` | boolean | Tidak | `1`, `0`, `true`, `false` | Scope filter closed threads | - |
| `filter[resolved]` | boolean | Tidak | `1`, `0`, `true`, `false` | Scope filter resolved threads | - |
| `filter[is_mentioned]` | boolean | Tidak | `1`, `0`, `true`, `false` | Scope filter thread dimana user di-tag | - |

**Available Includes:**
- `author` - Include thread author information
- `course` - Include course information
- `media` - Include thread attachments
- `tags` - Include thread tags
- `replies` - Include flat list of replies
- `topLevelReplies` - Include top-level replies (root of nested structure)
- `topLevelReplies.children` - Include nested replies (recursive, e.g., `topLevelReplies.children.children`)
- `topLevelReplies.author` - Include author of top-level replies
- `topLevelReplies.media` - Include attachments of top-level replies

**Examples:**
```http
# Include author and nested replies (2 levels deep)
GET /api/v1/courses/web-development-course/forum/threads?include=author,topLevelReplies.author,topLevelReplies.children.author

# Deep nesting (up to 10 levels supported)
GET /api/v1/courses/web-development-course/forum/threads?include=topLevelReplies.children.children.children.author

# Filter by pinned and sort by most viewed
GET /api/v1/courses/web-development-course/forum/threads?filter[pinned]=1&sort=-views_count
```

**Contoh Request (Course Forum):**
```http
GET /api/v1/courses/web-development-course/forum/threads?page=1&per_page=20&sort=-is_pinned,-last_activity_at
```

**Contoh Request (dengan filter dan search):**
```http
GET /api/v1/courses/web-development-course/forum/threads?search=laravel&filter[is_pinned]=1&filter[author_id]=10&sort=-replies_count&per_page=10
```



---

### 2. Membuat Thread Baru
Membuat topik diskusi baru dengan opsional file attachment (photo, video, PDF). User harus terdaftar/enroll dalam course.

**Endpoint:** `POST /courses/{course_slug}/forum/threads`

**Content-Type:** `multipart/form-data`

**Body Fields:**
| Key | Tipe | Wajib | Allowed Values | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `title` | string | Ya | 3-255 characters | Judul thread (min 3, max 255 karakter) |
| `content` | string | Ya | 1-5000 characters, no XSS | Isi konten thread (min 1, max 5000 karakter). Gunakan `@username` untuk men-tag user. |
| `attachments[]` | file | Tidak | jpeg, png, jpg, gif, pdf, mp4, webm, ogg, mov, avi (max 50MB per file) | File attachment (max 5 files, up to 50MB each) |

**Validasi:**
- Title: `min:3|max:255|required|string`
- Content: `min:1|max:5000|required|string`
- Attachments: `nullable|array|max:5` (max 5 files)
- Each attachment: `file|mimes:jpeg,png,jpg,gif,pdf,mp4,webm,ogg,mov,avi|max:51200` (50MB)
- No XSS patterns allowed
- User must be enrolled in the course

**Contoh Request (dengan curl - form-data):**
```bash
curl -X POST http://localhost:8000/api/v1/courses/web-development-course/forum/threads \
  -H "Authorization: Bearer {token}" \
  -F "title=Setup Laravel with Docker" \
  -F "content=Bagaimana cara setup Laravel menggunakan Docker?" \
  -F "attachments=@/path/to/image1.jpg" \
  -F "attachments=@/path/to/setup-guide.pdf" \
  -F "attachments=@/path/to/tutorial.mp4"
```

**Contoh JSON Response (201 Created):**
```json
{
  "success": true,
  "message": "Thread created successfully.",
  "data": {
    "id": 5,
    "title": "Setup Laravel with Docker",
    "content": "Bagaimana cara setup Laravel menggunakan Docker?",
    "author": {
      "id": 17,
      "username": "john.doe",
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": "https://example.com/avatar.jpg"
    },
    "course_slug": "web-development-course",
    "course": {
      "slug": "web-development-course",
      "title": "Web Development Course"
    },
    "is_pinned": false,
    "is_closed": false,
    "is_resolved": false,
    "is_mentioned": true,
    "views_count": 0,
    "replies_count": 0,
    "attachments": [
      {
        "id": 1,
        "name": "image1",
        "file_name": "image1.jpg",
        "mime_type": "image/jpeg",
        "size": 2048576,
        "url": "https://storage.example.com/threads/5/image1.jpg",
        "thumb_url": "https://storage.example.com/threads/5/conversions/image1-thumb.jpg",
        "preview_url": "https://storage.example.com/threads/5/conversions/image1-preview.jpg"
      },
      {
        "id": 2,
        "name": "setup-guide",
        "file_name": "setup-guide.pdf",
        "mime_type": "application/pdf",
        "size": 5242880,
        "url": "https://storage.example.com/threads/5/setup-guide.pdf",
        "thumb_url": "https://storage.example.com/threads/5/conversions/setup-guide-thumb.jpg",
        "preview_url": "https://storage.example.com/threads/5/conversions/setup-guide-preview.jpg"
      },
      {
        "id": 3,
        "name": "tutorial",
        "file_name": "tutorial.mp4",
        "mime_type": "video/mp4",
        "size": 52428800,
        "url": "https://storage.example.com/threads/5/tutorial.mp4",
        "thumb_url": "https://storage.example.com/threads/5/conversions/tutorial-thumb.jpg",
        "preview_url": "https://storage.example.com/threads/5/conversions/tutorial-preview.jpg"
      }
    ],
    "created_at": "2026-02-03T10:00:00Z",
    "updated_at": "2026-02-03T10:00:00Z"
  }
}
```

---

### 3. Detail Thread
Melihat detail satu thread beserta relasinya (replies, reactions, author info).

**Endpoint:** `GET /courses/{course_slug}/forum/threads/{thread_id}`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `thread_id` | integer | ID thread yang ingin dilihat |

**Query Parameters:**
| Parameter | Tipe | Wajib | Allowed Values | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `include` | string | Tidak | `author`, `course`, `media`, `tags`, `topLevelReplies`, `topLevelReplies.children` (recursive), ... | Comma-separated relationships to include. Use `topLevelReplies.children` (repeated) for deeper levels. |

**Available Includes:**
- `author` - Include thread author information
- `course` - Include course information
- `media` - Include thread attachments
- `tags` - Include thread tags
- `topLevelReplies` - Include top-level replies (root)
- `topLevelReplies.author` - Include author of top-level replies
- `topLevelReplies.media` - Include attachments of top-level replies
- `topLevelReplies.children` - Include 2nd level replies
- `topLevelReplies.children.children` - Include 3rd level replies (and so on, up to 10 levels)
- `topLevelReplies.children.author` - Include author of 2nd level replies

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Thread retrieved successfully.",
  "data": {
    "id": 5,
    "title": "Bagaimana cara install Laravel?",
    "content": "Saya mengalami kendala saat install composer...",
    "author": {
      "id": 17,
      "username": "john.doe",
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": "https://example.com/avatar.jpg"
    },
    "course_slug": "web-development-course",
    "is_pinned": false,
    "is_closed": false,
    "is_resolved": false,
    "is_mentioned": false,
    "views_count": 150,
    "replies_count": 25,
    "last_activity_at": "2026-02-03T15:30:00Z",
    "created_at": "2026-02-03T10:00:00Z",
    "updated_at": "2026-02-03T10:00:00Z"
  }
}
```

---

### 4. Update Thread
Mengubah judul atau konten thread. Hanya author atau moderator yang bisa update.

**Endpoint:** `PATCH /courses/{course_slug}/forum/threads/{thread_id}`

**Body (JSON):**
| Key | Tipe | Wajib | Allowed Values | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `title` | string | Tidak | 3-255 characters | Judul baru (min 3, max 255 karakter) |
| `content` | string | Tidak | 1-5000 characters, no XSS | Konten baru (min 1, max 5000 karakter) |

**Validasi:**
- Minimal salah satu field harus ada (title atau content)
- Content tidak boleh kosong
- Authorized: author atau moderator saja
- No XSS patterns allowed

**Contoh JSON:**
```json
{
    "title": "Judul diperbarui",
    "content": "Konten diperbarui dengan informasi yang lebih lengkap"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Thread updated successfully.",
  "data": {
    "id": 5,
    "title": "Judul diperbarui",
    "content": "Konten diperbarui dengan informasi yang lebih lengkap",
    "edited_at": "2026-02-03T11:00:00Z"
  }
}
```

---

### 5. Hapus Thread
Menghapus thread (Soft Delete). Hanya author atau moderator yang bisa hapus.

**Endpoint:** `DELETE /courses/{course_slug}/forum/threads/{thread_id}`

**Authorization:**
- Author (pembuat thread)
- Moderator (instructor untuk course tersebut)
- Admin/Superadmin

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Thread deleted successfully."
}
```

---

### 6. Pin Thread
Menyematkan thread agar muncul di atas (hanya moderator).

**Endpoint:** `PATCH /courses/{course_slug}/forum/threads/{thread_id}/pin`

**Authorization:** Moderator (instructor), Admin, atau Superadmin saja

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Thread pinned successfully.",
  "data": {
    "id": 5,
    "is_pinned": true
  }
}
```

---

### 7. Unpin Thread
Melepas sematan thread (hanya moderator).

**Endpoint:** `PATCH /courses/{course_slug}/forum/threads/{thread_id}/unpin`

**Authorization:** Moderator (instructor), Admin, atau Superadmin saja

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Thread unpinned successfully.",
  "data": {
    "id": 5,
    "is_pinned": false
  }
}
```

---

### 8. Close Thread
Menutup thread sehingga tidak bisa menerima reply baru (hanya moderator).

**Endpoint:** `PATCH /courses/{course_slug}/forum/threads/{thread_id}/close`

**Authorization:** Moderator (instructor), Admin, atau Superadmin saja

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Thread closed successfully.",
  "data": {
    "id": 5,
    "is_closed": true
  }
}
```

---

## Reply (Balasan)

### 1. Menampilkan Daftar Balasan
Menampilkan semua balasan dalam sebuah thread dengan pagination.

**Endpoint:** `GET /courses/{course_slug}/forum/threads/{thread_id}/replies`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `thread_id` | integer | ID thread yang ingin dilihat balasannya |

**Query Parameters:**
| Parameter | Tipe | Wajib | Allowed Values | Deskripsi | Default |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `page` | integer | Tidak | >= 1 | Halaman pagination | `1` |
| `per_page` | integer | Tidak | 1-100 | Jumlah item per halaman | `20` |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Replies retrieved successfully.",
  "data": [
    {
      "id": 1,
      "thread_id": 5,
      "author": {
        "id": 10,
        "username": "jane.smith",
        "name": "Jane Smith",
        "email": "jane@example.com",
        "avatar": "https://example.com/avatar.jpg"
      },
      "content": "Coba jalankan perintah composer install",
      "parent_id": null,
      "depth": 0,
      "created_at": "2026-02-01T09:00:00Z",
      "updated_at": "2026-02-01T09:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 25,
    "last_page": 2
  }
}
```

---

### 2. Membuat Balasan
Membalas sebuah thread atau membalas balasan lain (nested reply, max 5 level) dengan opsional file attachment.

**Endpoint:** `POST /courses/{course_slug}/forum/threads/{thread_id}/replies`

**Content-Type:** `multipart/form-data`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `thread_id` | integer | ID thread yang ingin dibalas |

**Body Fields:**
| Key | Tipe | Wajib | Allowed Values | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `content` | string | Ya | 1-5000 characters, no XSS | Isi balasan (min 1, max 5000 karakter). Gunakan `@username` untuk men-tag user. |
| `parent_id` | integer | Tidak | Valid reply ID in same thread | ID reply lain jika ingin membalas komentar (nested). Kosongkan jika membalas thread utama |
| `attachments[]` | file | Tidak | jpeg, png, jpg, gif, pdf, mp4, webm, ogg, mov, avi (max 50MB per file) | File attachment (max 5 files, up to 50MB each) |

**Validasi:**
- Content: `min:1|max:5000|required|string`
- Parent reply must exist in same thread
- Max nesting depth: 5 levels
- Thread tidak boleh closed
- Attachments: `nullable|array|max:5` (max 5 files)
- Each attachment: `file|mimes:jpeg,png,jpg,gif,pdf,mp4,webm,ogg,mov,avi|max:51200` (50MB)
- No XSS patterns allowed

**Contoh Request (curl - form-data, Reply to Thread):**
```bash
curl -X POST http://localhost:8000/api/v1/courses/web-development-course/forum/threads/5/replies \
  -H "Authorization: Bearer {token}" \
  -F "content=Coba jalankan perintah composer install. Lihat guide di bawah" \
  -F "parent_id=" \
  -F "attachments=@/path/to/installation-guide.pdf" \
  -F "attachments=@/path/to/screenshot.png"
```

**Contoh Request (curl - form-data, Nested Reply):**
```bash
curl -X POST http://localhost:8000/api/v1/courses/web-development-course/forum/threads/5/replies \
  -H "Authorization: Bearer {token}" \
  -F "content=Benar, atau bisa juga composer update jika sudah ada composer.lock" \
  -F "parent_id=1" \
  -F "attachments=@/path/to/reference.pdf"
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Reply created successfully.",
  "data": {
    "id": 2,
    "thread_id": 5,
    "author": {
      "id": 17,
      "username": "john.doe",
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": "https://example.com/avatar.jpg"
    },
    "content": "Coba jalankan perintah composer install. Lihat guide di bawah",
    "parent_id": null,
    "depth": 0,
    "attachments": [
      {
        "id": 6,
        "name": "installation-guide",
        "file_name": "installation-guide.pdf",
        "mime_type": "application/pdf",
        "size": 2048576,
        "url": "https://storage.example.com/replies/2/installation-guide.pdf",
        "thumb_url": "https://storage.example.com/replies/2/conversions/installation-guide-thumb.jpg",
        "preview_url": "https://storage.example.com/replies/2/conversions/installation-guide-preview.jpg"
      },
      {
        "id": 7,
        "name": "screenshot",
        "file_name": "screenshot.png",
        "mime_type": "image/png",
        "size": 1048576,
        "url": "https://storage.example.com/replies/2/screenshot.png",
        "thumb_url": "https://storage.example.com/replies/2/conversions/screenshot-thumb.jpg",
        "preview_url": "https://storage.example.com/replies/2/conversions/screenshot-preview.jpg"
      }
    ],
    "edited_at": null,
    "created_at": "2026-02-01T09:30:00Z",
    "updated_at": "2026-02-01T09:30:00Z"
  }
}
```

---

### 3. Update Balasan
Mengubah konten balasan. Hanya author atau moderator yang bisa update.

**Endpoint:** `PATCH /courses/{course_slug}/forum/replies/{reply_id}`

**Content-Type:** `multipart/form-data` or `application/json`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `reply_id` | integer | ID reply yang ingin diupdate |

**Body Fields:**
| Key | Tipe | Wajib | Allowed Values | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `content` | string | Ya | 1-5000 characters, no XSS | Konten baru (min 1, max 5000 karakter) |

**Validasi:**
- Content: `min:1|max:5000|required|string`
- No XSS patterns allowed
- Authorization: author atau moderator saja

**Contoh Request (curl):**
```bash
curl -X PATCH http://localhost:8000/api/v1/courses/web-development-course/forum/replies/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"content": "Koreksi: coba composer update"}'
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Reply updated successfully.",
  "data": {
    "id": 1,
    "content": "Koreksi: coba composer update",
    "edited_at": "2026-02-01T10:00:00Z"
  }
}
```

---

### 4. Hapus Balasan
Menghapus balasan (Soft Delete). Hanya author atau moderator yang bisa hapus.

**Endpoint:** `DELETE /courses/{course_slug}/forum/replies/{reply_id}`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `reply_id` | integer | ID reply yang ingin dihapus |

**Authorization:**
- Author (pembuat reply)
- Moderator (instructor untuk course tersebut)
- Admin/Superadmin

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Reply deleted successfully."
}
```

---

## Reaction (Reaksi)

### Tipe Reaksi
| Type | Deskripsi |
| :--- | :--- |
| `like` | Like/Suka konten |
| `helpful` | Konten sangat membantu |
| `solved` | Menandai reply sebagai solusi |

### 1. Upsert Reaksi Thread
Menambahkan atau menghapus (toggle) reaksi pada thread.

**Endpoint:** `POST /courses/{course_slug}/forum/threads/{thread_id}/reactions`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `thread_id` | integer | ID thread untuk ditambahkan reaksi |

**Body (JSON):**
| Key | Tipe | Wajib | Allowed Values | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `type` | string | Ya | `like`, `helpful`, `solved` | Tipe reaksi |

**Validasi:**
- Type: `required|in:like,helpful,solved`

**Contoh JSON:**
```json
{
    "type": "helpful"
}
```

**Response (200 OK - Added):**
```json
{
  "success": true,
  "message": "Reaction added successfully.",
  "data": {
    "id": 15,
    "type": "helpful",
    "reactionable_type": "Modules\\Forums\\Models\\Thread",
    "reactionable_id": 5,
    "user_id": 17
  }
}
```

**Response (200 OK - Toggled/Removed):**
```json
{
  "success": true,
  "message": "Reaction removed successfully."
}
```

---

### 2. Hapus Reaksi Thread
Menghapus reaksi dari thread.

**Endpoint:** `DELETE /courses/{course_slug}/forum/threads/{thread_id}/reactions/{reaction_id}`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `thread_id` | integer | ID thread |
| `reaction_id` | integer | ID reaksi yang ingin dihapus |

**Authorization:**
- Reaction owner (user yang membuat reaksi)
- Moderator (instructor)
- Admin/Superadmin

**Contoh:**
```http
DELETE /api/v1/courses/web-development-course/forum/threads/1/reactions/5
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Reaction removed successfully."
}
```

---

### 3. Upsert Reaksi Balasan
Menambahkan atau menghapus (toggle) reaksi pada balasan.

**Endpoint:** `POST /courses/{course_slug}/forum/replies/{reply_id}/reactions`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `reply_id` | integer | ID reply untuk ditambahkan reaksi |

**Body (JSON):**
| Key | Tipe | Wajib | Allowed Values | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `type` | string | Ya | `like`, `helpful`, `solved` | Tipe reaksi |

**Validasi:**
- Type: `required|in:like,helpful,solved`

**Contoh JSON:**
```json
{
    "type": "like"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Reaction added successfully.",
  "data": {
    "id": 16,
    "type": "like",
    "reactionable_type": "Modules\\Forums\\Models\\Reply",
    "reactionable_id": 10,
    "user_id": 17
  }
}
```

---

### 4. Hapus Reaksi Balasan
Menghapus reaksi dari balasan.

**Endpoint:** `DELETE /courses/{course_slug}/forum/replies/{reply_id}/reactions/{reaction_id}`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `reply_id` | integer | ID reply |
| `reaction_id` | integer | ID reaksi yang ingin dihapus |

**Authorization:**
- Reaction owner
- Moderator (instructor)
- Admin/Superadmin

**Contoh:**
```http
DELETE /api/v1/courses/web-development-course/forum/replies/10/reactions/15
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Reaction removed successfully."
}
```

---

## Statistik

### 1. Statistik Forum (Moderator Only)
Melihat statistik umum forum course (Instructor/Admin/Superadmin saja).

**Endpoint:** `GET /courses/{course_slug}/forum/statistics`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `course_slug` | string | Slug course |

**Query Parameters:**
| Parameter | Tipe | Wajib | Allowed Values | Deskripsi | Default |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `filter[period_start]` | date | Tidak | ISO 8601 format (Y-m-d) | Awal periode | Start of current month |
| `filter[period_end]` | date | Tidak | ISO 8601 format (Y-m-d) | Akhir periode (must be >= period_start) | End of current month |
| `filter[user_id]` | integer | Tidak | Valid user ID | (Opsional) Filter statistik user tertentu | - |

**Validasi:**
- `filter.period_start`: `nullable|date` (format: Y-m-d)
- `filter.period_end`: `nullable|date|after_or_equal:filter.period_start`
- `filter.user_id`: `nullable|integer|exists:users,id`

**Authorization:** Instructor, Admin, atau Superadmin saja

**Contoh Request (Course Statistics):**
```http
GET /api/v1/courses/web-development-course/forum/statistics?filter[period_start]=2026-02-01&filter[period_end]=2026-02-28
```

**Contoh Request (User Statistics dalam course):**
```http
GET /api/v1/courses/web-development-course/forum/statistics?filter[user_id]=10&filter[period_start]=2026-02-01&filter[period_end]=2026-02-28
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Forum statistics retrieved successfully.",
  "data": {
    "course_id": 1,
    "period_start": "2026-02-01",
    "period_end": "2026-02-28",
    "total_threads": 45,
    "total_replies": 320,
    "total_reactions": 850,
    "active_users": 28,
    "average_reply_time": "2.5 hours",
    "most_active_user": {
      "id": 17,
      "name": "John Doe",
      "contributions": 45
    }
  }
}
```

---

### 2. Statistik Saya
Melihat statistik aktivitas forum user yang sedang login.

**Endpoint:** `GET /courses/{course_slug}/forum/my-statistics`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `course_slug` | string | Slug course |

**Query Parameters:**
| Parameter | Tipe | Wajib | Allowed Values | Deskripsi | Default |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `filter[period_start]` | date | Tidak | ISO 8601 format (Y-m-d) | Awal periode | Start of current month |
| `filter[period_end]` | date | Tidak | ISO 8601 format (Y-m-d) | Akhir periode (must be >= period_start) | End of current month |

**Validasi:**
- `filter.period_start`: `nullable|date` (format: Y-m-d)
- `filter.period_end`: `nullable|date|after_or_equal:filter.period_start`

**Contoh Request:**
```http
GET /api/v1/courses/web-development-course/forum/my-statistics?filter[period_start]=2026-02-01&filter[period_end]=2026-02-28
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "User statistics retrieved successfully.",
  "data": {
    "user_id": 17,
    "course_id": 1,
    "period_start": "2026-02-01",
    "period_end": "2026-02-28",
    "threads_created": 5,
    "replies_created": 23,
    "reactions_given": 45,
    "reactions_received": 78,
    "last_activity_at": "2026-02-10T15:30:00Z"
  }
}
```

---

## Media & Attachments

### Attachment Overview

Forum threads dan replies mendukung file attachments untuk berbagi dokumen, gambar, video, dan PDF. Semua file disimpan di DigitalOcean Spaces dengan pengamanan akses.

**Supported File Types:**
| Kategori | File Types | MIME Types |
| :--- | :--- | :--- |
| **Images** | JPG, JPEG, PNG, GIF | `image/jpeg`, `image/png`, `image/gif` |
| **Documents** | PDF | `application/pdf` |
| **Videos** | MP4, WebM, OGG, MOV, AVI | `video/mp4`, `video/webm`, `video/ogg`, `video/quicktime`, `video/x-msvideo` |

**File Restrictions:**
- **Maximum files per upload:** 5 files
- **Maximum file size:** 50MB per file
- **Total upload size:** 250MB maximum per request (5 × 50MB)
- **Allowed MIME types:** jpeg, png, jpg, gif, pdf, mp4, webm, ogg, mov, avi

**Response Metadata:**

Setiap file dalam `attachments` array mengandung:

| Field | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | integer | Media ID unik di database |
| `name` | string | Nama file tanpa extension |
| `file_name` | string | Nama file lengkap dengan extension |
| `mime_type` | string | MIME type file |
| `size` | integer | Ukuran file dalam bytes |
| `url` | string | URL file original di DigitalOcean Spaces |
| `thumb_url` | string | URL gambar thumbnail (300×200px) |
| `preview_url` | string | URL gambar preview (800×600px) |

**Image Conversions:**
Setiap image/video secara otomatis dikonversi menjadi:
- `thumb`: Thumbnail 300×200px dengan sharpening
- `preview`: Preview 800×600px dengan sharpening

PDF dan non-image files menampilkan generic preview placeholder untuk `thumb_url` dan `preview_url`.

**Storage:**
- Disk: DigitalOcean Spaces (`do`)
- CDN: CloudFront (untuk fast delivery)
- Path: `/threads/{thread_id}/` atau `/replies/{reply_id}/`
- Public: Semua file public accessible via URL

---

### Uploading Attachments

Attachments dapat ditambahkan saat membuat atau memperbarui thread/reply dengan menggunakan `multipart/form-data` encoding.

**Contoh Request dengan Multiple Attachments:**

```bash
curl -X POST http://localhost:8000/api/v1/courses/web-development-course/forum/threads \
  -H "Authorization: Bearer {token}" \
  -F "forumable_type=Modules\\Schemes\\Models\\Course" \
  -F "title=Sharing: Database Design Pattern" \
  -F "content=Database design patterns yang biasa saya gunakan di project" \
  -F "attachments=@database-design.pdf" \
  -F "attachments=@diagram-1.png" \
  -F "attachments=@diagram-2.png" \
  -F "attachments=@tutorial-video.mp4"
```

**Form Data Structure:**
```
title: "Sharing: Database Design Pattern"
content: "Database design patterns yang biasa saya gunakan di project"
attachments[0]: <binary data for database-design.pdf>
attachments[1]: <binary data for diagram-1.png>
attachments[2]: <binary data for diagram-2.png>
attachments[3]: <binary data for tutorial-video.mp4>
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Thread created successfully.",
  "data": {
    "id": 15,
    "title": "Sharing: Database Design Pattern",
    "content": "Database design patterns yang biasa saya gunakan di project",
    "author": {
      "id": 17,
      "username": "john.doe",
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": "https://example.com/avatar.jpg"
    },
    "course_slug": "web-development-course",
    "is_pinned": false,
    "is_closed": false,
    "is_resolved": false,
    "views_count": 0,
    "replies_count": 0,
    "attachments": [
      {
        "id": 51,
        "name": "database-design",
        "file_name": "database-design.pdf",
        "mime_type": "application/pdf",
        "size": 2048576,
        "url": "https://cdn.example.com/storage/threads/15/database-design.pdf",
        "thumb_url": "https://cdn.example.com/storage/threads/15/conversions/database-design-thumb.jpg",
        "preview_url": "https://cdn.example.com/storage/threads/15/conversions/database-design-preview.jpg"
      },
      {
        "id": 52,
        "name": "diagram-1",
        "file_name": "diagram-1.png",
        "mime_type": "image/png",
        "size": 1048576,
        "url": "https://cdn.example.com/storage/threads/15/diagram-1.png",
        "thumb_url": "https://cdn.example.com/storage/threads/15/conversions/diagram-1-thumb.jpg",
        "preview_url": "https://cdn.example.com/storage/threads/15/conversions/diagram-1-preview.jpg"
      },
      {
        "id": 53,
        "name": "diagram-2",
        "file_name": "diagram-2.png",
        "mime_type": "image/png",
        "size": 1048576,
        "url": "https://cdn.example.com/storage/threads/15/diagram-2.png",
        "thumb_url": "https://cdn.example.com/storage/threads/15/conversions/diagram-2-thumb.jpg",
        "preview_url": "https://cdn.example.com/storage/threads/15/conversions/diagram-2-preview.jpg"
      },
      {
        "id": 54,
        "name": "tutorial-video",
        "file_name": "tutorial-video.mp4",
        "mime_type": "video/mp4",
        "size": 52428800,
        "url": "https://cdn.example.com/storage/threads/15/tutorial-video.mp4",
        "thumb_url": "https://cdn.example.com/storage/threads/15/conversions/tutorial-video-thumb.jpg",
        "preview_url": "https://cdn.example.com/storage/threads/15/conversions/tutorial-video-preview.jpg"
      }
    ],
    "created_at": "2026-02-03T14:25:00Z",
    "updated_at": "2026-02-03T14:25:00Z"
  }
}
```

**Error Response - Validation Failed:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "attachments.0": ["The attachments.0 field may not be greater than 51200 kilobytes."],
    "attachments.1": ["The attachments.1 must be a file of type: jpeg, png, jpg, gif, pdf, mp4, webm, ogg, mov, avi."],
    "attachments": ["The attachments must not have more than 5 items."]
  }
}
```

---

### Accessing Attachments

Attachments tersedia dalam setiap response dari:
- **GET /threads** - List threads
- **GET /threads/{id}** - Thread detail
- **GET /threads/{id}/replies** - List replies
- **POST /threads** - Create thread
- **POST /threads/{id}/replies** - Create reply
- **PATCH /threads/{id}** - Update thread
- **PATCH /replies/{id}** - Update reply

**Contoh cara menggunakan di frontend:**

```javascript
// Menampilkan thread dengan attachments
const thread = await fetchThread(courseSlug, threadId);

thread.attachments.forEach(attachment => {
  console.log(`File: ${attachment.file_name} (${attachment.size} bytes)`);
  console.log(`URL: ${attachment.url}`);
  console.log(`Preview: ${attachment.preview_url}`);
});

// Menampilkan thumbnail untuk image files
const imageAttachments = thread.attachments.filter(a => 
  ['image/jpeg', 'image/png', 'image/gif'].includes(a.mime_type)
);

imageAttachments.forEach(img => {
  const thumbnailHtml = `<img src="${img.thumb_url}" alt="${img.name}" />`;
  document.body.insertAdjacentHTML('beforeend', thumbnailHtml);
});
```

---

## Contoh Penggunaan

### Skenario 1: Membuat Thread Course dan Reply dengan Reaction

```bash
# 1. Create thread di course forum
curl -X POST http://localhost:8000/api/v1/courses/web-development-course/forum/threads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "forumable_type": "Modules\\\\Schemes\\\\Models\\\\Course",
    "forumable_slug": "web-development-course",
    "title": "Bagaimana cara setup project?",
    "content": "Saya ingin tahu langkah-langkah setup project menggunakan Docker"
  }'

# Response:
# {
#   "success": true,
#   "message": "Thread created successfully.",
#   "data": {
#     "id": 5,
#     "title": "Bagaimana cara setup project?",
#     ...
#   }
# }

# 2. Create reply ke thread
curl -X POST http://localhost:8000/api/v1/courses/web-development-course/forum/threads/5/replies \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Coba ikuti dokumentasi di README.md untuk setup dengan Docker",
    "parent_id": null
  }'

# 3. Create nested reply (balasan ke balasan)
curl -X POST http://localhost:8000/api/v1/courses/web-development-course/forum/threads/5/replies \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Betul, atau bisa juga pakai docker-compose up",
    "parent_id": 10
  }'

# 4. Add reaction to reply
curl -X POST http://localhost:8000/api/v1/courses/web-development-course/forum/replies/10/reactions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "helpful"
  }'
```

---



---

### Skenario 3: Moderator/Admin Actions

```bash
# 1. View trending threads (last 7 days)
curl -X GET "http://localhost:8000/api/v1/forums/threads/trending?filter[period]=7days&filter[limit]=10" \
  -H "Authorization: Bearer {instructor_token}"

# 2. Pin a thread (instructor only)
curl -X PATCH http://localhost:8000/api/v1/courses/web-development-course/forum/threads/5/pin \
  -H "Authorization: Bearer {instructor_token}"

# 3. Close a thread (disable new replies)
curl -X PATCH http://localhost:8000/api/v1/courses/web-development-course/forum/threads/5/close \
  -H "Authorization: Bearer {instructor_token}"

# 4. Unpin a thread
curl -X PATCH http://localhost:8000/api/v1/courses/web-development-course/forum/threads/5/unpin \
  -H "Authorization: Bearer {instructor_token}"
```

---

### Skenario 4: Forum Statistics

```bash
# 1. Get forum statistics for current month
curl -X GET "http://localhost:8000/api/v1/courses/web-development-course/forum/statistics" \
  -H "Authorization: Bearer {instructor_token}"

# 2. Get forum statistics for specific period
curl -X GET "http://localhost:8000/api/v1/courses/web-development-course/forum/statistics?filter[period_start]=2026-02-01&filter[period_end]=2026-02-28" \
  -H "Authorization: Bearer {instructor_token}"

# 3. Get specific user statistics in course
curl -X GET "http://localhost:8000/api/v1/courses/web-development-course/forum/statistics?filter[user_id]=17" \
  -H "Authorization: Bearer {instructor_token}"

# 4. Get my personal statistics
curl -X GET "http://localhost:8000/api/v1/courses/web-development-course/forum/my-statistics" \
  -H "Authorization: Bearer {student_token}"
```

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."],
    "content": ["The content must be at least 1 characters."]
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
  "message": "Thread not found."
}
```

---

## Appendix: Media Implementation Details

### Models & Storage
- **Models**: `Thread` and `Reply` implement `HasMedia` and use `InteractsWithMedia`.
- **Disk**: `do` (DigitalOcean Spaces).
- **Paths**: `/threads/{id}/` and `/replies/{id}/`.
- **Conversions**: `thumb` (300x200), `preview` (800x600).

### Service Logic
Transaction-safe media handling is implemented in `ForumService`.
- **Creation**: Media is added to `attachments` collection inside the DB transaction.
- **Lazy Loading**: Use `with(['media'])` or `load('media')` to prevent N+1 queries. The API resources (`ThreadResource`, `ReplyResource`) automatically handle media transformation when loaded.

### Testing Scenarios
1.  **File Upload Validation**
    *   Test max 5 files limit
    *   Test 50MB size limit
    *   Test unsupported file types (e.g. .exe, .zip)
2.  **Media Persistence**
    *   Create thread with media -> Verify files on DO Spaces
    *   Create reply with media -> Verify files on DO Spaces
3.  **API Response**
    *   Verify attachments array contains `url`, `thumb_url`, `preview_url`
4.  **Lazy Loading**
    *   Verify media loads with eager loading to prevent N+1 queries.

