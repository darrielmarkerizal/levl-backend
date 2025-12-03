# Content Module API Documentation

## Overview

Content Module menyediakan API untuk mengelola pengumuman (announcements) dan berita (news) dalam platform LMS.

## Base URL

```
/api/v1
```

## Authentication

Semua endpoint memerlukan autentikasi menggunakan JWT token.

```
Authorization: Bearer {token}
```

---

## Announcements

### 1. Get All Announcements

Mendapatkan daftar pengumuman untuk user yang sedang login.

**Endpoint:** `GET /announcements`

**Query Parameters:**
- `course_id` (optional): Filter by course ID
- `priority` (optional): Filter by priority (low, normal, high)
- `unread` (optional): Filter unread announcements (true/false)
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Selamat Datang",
        "content": "Selamat datang di platform...",
        "status": "published",
        "target_type": "all",
        "priority": "high",
        "published_at": "2025-12-01T10:00:00.000000Z",
        "author": {
          "id": 1,
          "name": "Admin"
        }
      }
    ],
    "total": 10
  }
}
```

### 2. Create Announcement

Membuat pengumuman baru (Admin only).

**Endpoint:** `POST /announcements`

**Request Body:**
```json
{
  "title": "Pengumuman Penting",
  "content": "Ini adalah pengumuman penting...",
  "target_type": "all",
  "target_value": null,
  "priority": "high",
  "status": "draft",
  "scheduled_at": null
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Pengumuman berhasil dibuat.",
  "data": {
    "id": 1,
    "title": "Pengumuman Penting",
    "status": "draft",
    "created_at": "2025-12-02T10:00:00.000000Z"
  }
}
```

### 3. Get Announcement Detail

Mendapatkan detail pengumuman.

**Endpoint:** `GET /announcements/{id}`

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Pengumuman Penting",
    "content": "Ini adalah pengumuman...",
    "author": {
      "id": 1,
      "name": "Admin"
    },
    "revisions": []
  }
}
```

### 4. Update Announcement

Mengupdate pengumuman.

**Endpoint:** `PUT /announcements/{id}`

**Request Body:**
```json
{
  "title": "Pengumuman Updated",
  "content": "Konten yang diupdate..."
}
```

### 5. Delete Announcement

Menghapus pengumuman (soft delete).

**Endpoint:** `DELETE /announcements/{id}`

**Response:**
```json
{
  "status": "success",
  "message": "Pengumuman berhasil dihapus."
}
```

### 6. Publish Announcement

Mempublikasikan pengumuman.

**Endpoint:** `POST /announcements/{id}/publish`

**Response:**
```json
{
  "status": "success",
  "message": "Pengumuman berhasil dipublikasikan.",
  "data": {
    "id": 1,
    "status": "published",
    "published_at": "2025-12-02T10:00:00.000000Z"
  }
}
```

### 7. Schedule Announcement

Menjadwalkan publikasi pengumuman.

**Endpoint:** `POST /announcements/{id}/schedule`

**Request Body:**
```json
{
  "scheduled_at": "2025-12-10T10:00:00Z"
}
```

### 8. Mark as Read

Menandai pengumuman sudah dibaca.

**Endpoint:** `POST /announcements/{id}/read`

**Response:**
```json
{
  "status": "success",
  "message": "Pengumuman ditandai sudah dibaca."
}
```

---

## News

### 1. Get All News

Mendapatkan daftar berita.

**Endpoint:** `GET /news`

**Query Parameters:**
- `category_id` (optional): Filter by category
- `tag_id` (optional): Filter by tag
- `featured` (optional): Filter featured news (true/false)
- `date_from` (optional): Filter from date
- `date_to` (optional): Filter to date
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Berita Terbaru",
        "slug": "berita-terbaru",
        "excerpt": "Ringkasan berita...",
        "featured_image_path": "/images/news.jpg",
        "is_featured": true,
        "published_at": "2025-12-01T10:00:00.000000Z",
        "views_count": 150,
        "author": {
          "id": 1,
          "name": "Admin"
        },
        "categories": []
      }
    ]
  }
}
```

### 2. Create News

Membuat berita baru (Admin/Instructor).

**Endpoint:** `POST /news`

**Request Body:**
```json
{
  "title": "Berita Baru",
  "slug": "berita-baru",
  "excerpt": "Ringkasan berita...",
  "content": "Konten lengkap berita...",
  "featured_image_path": "/images/news.jpg",
  "is_featured": false,
  "status": "draft",
  "category_ids": [1, 2],
  "tag_ids": [1, 2, 3]
}
```

### 3. Get News Detail

Mendapatkan detail berita.

**Endpoint:** `GET /news/{slug}`

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Berita Terbaru",
    "content": "Konten lengkap...",
    "views_count": 151,
    "author": {
      "id": 1,
      "name": "Admin"
    },
    "categories": [],
    "tags": []
  }
}
```

### 4. Update News

Mengupdate berita.

**Endpoint:** `PUT /news/{slug}`

**Request Body:**
```json
{
  "title": "Berita Updated",
  "content": "Konten yang diupdate...",
  "category_ids": [1, 3]
}
```

### 5. Delete News

Menghapus berita (soft delete).

**Endpoint:** `DELETE /news/{slug}`

### 6. Publish News

Mempublikasikan berita.

**Endpoint:** `POST /news/{slug}/publish`

### 7. Schedule News

Menjadwalkan publikasi berita.

**Endpoint:** `POST /news/{slug}/schedule`

**Request Body:**
```json
{
  "scheduled_at": "2025-12-10T10:00:00Z"
}
```

### 8. Get Trending News

Mendapatkan berita trending.

**Endpoint:** `GET /news/trending`

**Query Parameters:**
- `limit` (optional): Number of items (default: 10)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Berita Trending",
      "views_count": 500,
      "trending_score": 125.5
    }
  ]
}
```

---

## Course Announcements

### 1. Get Course Announcements

Mendapatkan pengumuman untuk kursus tertentu.

**Endpoint:** `GET /courses/{course}/announcements`

**Response:**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Pengumuman Kursus",
        "course_id": 1,
        "published_at": "2025-12-01T10:00:00.000000Z"
      }
    ]
  }
}
```

### 2. Create Course Announcement

Membuat pengumuman untuk kursus (Instructor/Admin).

**Endpoint:** `POST /courses/{course}/announcements`

**Request Body:**
```json
{
  "title": "Pengumuman Kursus",
  "content": "Konten pengumuman...",
  "priority": "normal",
  "status": "published"
}
```

---

## Statistics

### 1. Get Overall Statistics

Mendapatkan statistik keseluruhan.

**Endpoint:** `GET /content/statistics`

**Query Parameters:**
- `type` (optional): Type of content (all, announcements, news)
- `course_id` (optional): Filter by course
- `category_id` (optional): Filter by category
- `date_from` (optional): From date
- `date_to` (optional): To date

**Response:**
```json
{
  "status": "success",
  "data": {
    "dashboard": {
      "total_announcements": 50,
      "total_news": 100,
      "total_views": 5000,
      "recent_announcements": 10,
      "recent_news": 20
    },
    "announcements": [],
    "news": []
  }
}
```

### 2. Get Announcement Statistics

Mendapatkan statistik pengumuman tertentu.

**Endpoint:** `GET /content/statistics/announcements/{id}`

**Response:**
```json
{
  "status": "success",
  "data": {
    "announcement_id": 1,
    "title": "Pengumuman",
    "views_count": 100,
    "read_count": 80,
    "read_rate": 80.0,
    "unread_count": 20,
    "unread_users": []
  }
}
```

### 3. Get News Statistics

Mendapatkan statistik berita tertentu.

**Endpoint:** `GET /content/statistics/news/{slug}`

### 4. Get Trending Statistics

Mendapatkan berita trending.

**Endpoint:** `GET /content/statistics/trending`

### 5. Get Most Viewed

Mendapatkan berita paling banyak dilihat.

**Endpoint:** `GET /content/statistics/most-viewed`

**Query Parameters:**
- `days` (optional): Time period in days (default: 30)
- `limit` (optional): Number of items (default: 10)

---

## Search

### Search Content

Mencari konten (berita dan pengumuman).

**Endpoint:** `GET /content/search`

**Query Parameters:**
- `q` (required): Search query (min 2 characters)
- `type` (optional): Content type (all, news, announcements)
- `category_id` (optional): Filter by category
- `date_from` (optional): From date
- `date_to` (optional): To date
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Hasil Pencarian",
        "excerpt": "Ringkasan...",
        "type": "news"
      }
    ]
  }
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "status": "error",
  "message": "Anda belum login atau sesi Anda telah berakhir."
}
```

### 403 Forbidden
```json
{
  "status": "error",
  "message": "Akses ditolak. Anda tidak memiliki izin untuk melakukan aksi ini."
}
```

### 404 Not Found
```json
{
  "status": "error",
  "message": "Data tidak ditemukan."
}
```

### 422 Validation Error
```json
{
  "status": "error",
  "message": "Data yang Anda kirim tidak valid.",
  "errors": {
    "title": ["Judul wajib diisi."],
    "content": ["Konten wajib diisi."]
  }
}
```

---

## Notes

- Semua timestamp menggunakan format ISO 8601 (UTC)
- Pagination menggunakan format Laravel standard
- Soft delete digunakan untuk semua penghapusan data
- Full-text search tersedia untuk pencarian konten
