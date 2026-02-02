# Dokumentasi API Module Common

Dokumentasi lengkap untuk semua endpoint API di Module Common.

## Base URL
Semua endpoint menggunakan prefix `/api/v1`

## Autentikasi & Otorisasi

### Public Access
- Semua endpoint **GET** dapat diakses tanpa autentikasi

### Protected Access
- Endpoint **POST/PUT/DELETE** memerlukan autentikasi dengan header:
  ```
  Authorization: Bearer {access_token}
  ```
- **Hanya Superadmin** yang dapat melakukan operasi CRUD

## Fitur Query

### Pagination, Sorting, Filtering
Menggunakan **Spatie Query Builder** dengan format:

**Pagination:**
- `per_page`: Jumlah item per halaman (default: 15)
- `page`: Nomor halaman

**Sorting:**
- `sort`: Field untuk sorting
- Prefix dengan `-` untuk descending (contoh: `-created_at`)

**Filtering:**
- `filter[field]`: Filter berdasarkan field tertentu
- `search`: Full-text search menggunakan Meilisearch

**Contoh:**
```
GET /badges?filter[type]=achievement&sort=-created_at&per_page=20
GET /badges?search=complete&page=2
```

---

## 1. Audit Logs API

Endpoint untuk mengelola dan melihat log audit sistem.

### 1.1 List Audit Logs

**Endpoint:**
```
GET /audit-logs
```

**Autentikasi:** Required (Admin/Superadmin)

**Query Parameters:**
| Parameter | Type | Required | Deskripsi | Nilai yang Diizinkan |
|-----------|------|----------|-----------|---------------------|
| `action` | string | No | Filter berdasarkan satu jenis aksi | `submission_created`, `state_transition`, `grading`, `answer_key_change`, `grade_override`, `override_grant` |
| `actions` | array | No | Filter berdasarkan beberapa jenis aksi | Array dari nilai aksi di atas |
| `actor_id` | integer | No | Filter berdasarkan ID user yang melakukan aksi | ID user |
| `actor_role` | string | No | Filter berdasarkan role user | Role name |
| `subject_type` | string | No | Filter berdasarkan tipe subjek | Model class name |
| `subject_id` | integer | No | Filter berdasarkan ID subjek | ID subjek |
| `assessment_id` | integer | No | Filter berdasarkan ID assessment | ID assessment |
| `course_id` | integer | No | Filter berdasarkan ID course | ID course |
| `from_date` | date | No | Filter dari tanggal | Format: Y-m-d (2026-02-01) |
| `to_date` | date | No | Filter sampai tanggal | Format: Y-m-d (2026-02-28) |
| `sort` | string | No | Sorting | `created_at`, `-created_at` (default) |
| `per_page` | integer | No | Jumlah item per halaman | 1-100 (default: 15) |
| `page` | integer | No | Nomor halaman | >= 1 |

**Contoh Request:**
```bash
GET /api/v1/audit-logs?action=grading&actor_id=123&from_date=2026-02-01&to_date=2026-02-28&per_page=20
```

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "audit_logs": [
      {
        "id": 1,
        "action": "grading",
        "actor": {
          "id": 123,
          "name": "John Doe",
          "email": "john@example.com",
          "role": "Instructor"
        },
        "subject_type": "Submission",
        "subject_id": 456,
        "metadata": {
          "grade": 85,
          "previous_grade": null
        },
        "ip_address": "192.168.1.1",
        "user_agent": "Mozilla/5.0...",
        "created_at": "2026-02-15T10:30:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "total": 50,
      "per_page": 20,
      "last_page": 3
    }
  }
}
```

### 1.2 Show Audit Log Detail

**Endpoint:**
```
GET /audit-logs/{id}
```

**Autentikasi:** Required (Admin/Superadmin)

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID audit log |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "audit_log": {
      "id": 1,
      "action": "grading",
      "actor": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "subject_type": "Submission",
      "subject_id": 456,
      "metadata": {
        "grade": 85,
        "comments": "Good work"
      },
      "created_at": "2026-02-15T10:30:00.000000Z"
    }
  }
}
```

### 1.3 Get Available Actions

**Endpoint:**
```
GET /audit-logs/meta/actions
```

**Autentikasi:** Required (Admin/Superadmin)

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "actions": [
      "submission_created",
      "state_transition",
      "grading",
      "answer_key_change",
      "grade_override",
      "override_grant"
    ]
  }
}
```

---

## 2. Badges API

Endpoint untuk mengelola badge/lencana gamifikasi.

### 2.1 List All Badges

**Endpoint:**
```
GET /badges
```

**Autentikasi:** Public (tidak perlu autentikasi)

**Query Parameters (Spatie Query Builder):**
| Parameter | Type | Required | Deskripsi | Nilai yang Diizinkan |
|-----------|------|----------|-----------|---------------------|
| `search` | string | No | Full-text search menggunakan Meilisearch | Kata kunci pencarian |
| `filter[id]` | integer | No | Filter berdasarkan ID exact | ID badge |
| `filter[code]` | string | No | Filter berdasarkan code (partial match) | Kode badge |
| `filter[name]` | string | No | Filter berdasarkan name (partial match) | Nama badge |
| `filter[type]` | string | No | Filter berdasarkan type exact | `achievement`, `milestone`, `completion` |
| `sort` | string | No | Sorting | `id`, `code`, `name`, `type`, `threshold`, `created_at`, `updated_at` (prefix `-` untuk desc) |
| `per_page` | integer | No | Jumlah item per halaman | 1-100 (default: 15) |
| `page` | integer | No | Nomor halaman | >= 1 |

**Contoh Request:**
```bash
GET /api/v1/badges
GET /api/v1/badges?filter[type]=achievement&sort=-created_at
GET /api/v1/badges?search=complete&per_page=20
GET /api/v1/badges?filter[name]=first&sort=name
```

**Response Sukses (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "first_lesson",
      "name": "First Lesson Complete",
      "description": "Complete your first lesson",
      "type": "achievement",
      "threshold": 1,
      "icon_url": "https://storage.example.com/badges/first_lesson.png",
      "icon_thumb_url": "https://storage.example.com/badges/first_lesson_thumb.png",
      "created_at": "2026-02-01T00:00:00.000000Z",
      "updated_at": "2026-02-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "to": 15,
    "total": 25,
    "per_page": 15,
    "last_page": 2
  }
}
```

### 2.2 Show Badge Detail

**Endpoint:**
```
GET /badges/{id}
```

**Autentikasi:** Public (tidak perlu autentikasi)

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID badge |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "code": "first_lesson",
    "name": "First Lesson Complete",
    "description": "Complete your first lesson",
    "type": "achievement",
    "threshold": 1,
    "icon_url": "https://storage.example.com/badges/first_lesson.png",
    "icon_thumb_url": "https://storage.example.com/badges/first_lesson_thumb.png",
    "created_at": "2026-02-01T00:00:00.000000Z",
    "updated_at": "2026-02-01T00:00:00.000000Z"
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Badge not found."
}
```

### 2.3 Create Badge

**Endpoint:**
```
POST /badges
```

**Autentikasi:** Required (Superadmin only)

**Content-Type:** `multipart/form-data`

**Request Body:**
| Field | Type | Required | Deskripsi | Validasi | Nilai yang Diizinkan |
|-------|------|----------|-----------|----------|---------------------|
| `code` | string | Yes | Kode unik badge | max:50, unique | Contoh: `first_lesson`, `ten_courses` |
| `name` | string | Yes | Nama badge | max:255 | Contoh: "First Lesson Complete" |
| `description` | string | No | Deskripsi badge | max:1000 | Penjelasan lengkap badge |
| `type` | string | Yes | Tipe badge | enum | `achievement`, `milestone`, `completion` |
| `threshold` | integer | No | Threshold untuk mendapatkan badge | min:1 | Jumlah minimal (contoh: 1, 5, 10) |
| `icon` | file | No | File gambar icon badge | mimes:jpeg,png,svg,webp, max:2048KB | Upload file gambar |

**Contoh Request (Form Data):**
```
code: first_lesson
name: First Lesson Complete
description: Complete your first lesson
type: achievement
threshold: 1
icon: [file upload]
```

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Badge created successfully.",
  "data": {
    "id": 1,
    "code": "first_lesson",
    "name": "First Lesson Complete",
    "description": "Complete your first lesson",
    "type": "achievement",
    "threshold": 1,
    "icon_url": "https://storage.example.com/badges/first_lesson.png",
    "icon_thumb_url": "https://storage.example.com/badges/first_lesson_thumb.png",
    "created_at": "2026-02-15T10:30:00.000000Z",
    "updated_at": "2026-02-15T10:30:00.000000Z"
  }
}
```

**Response Error (403):**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "code": ["The code has already been taken."],
    "type": ["The type field must be one of: achievement, milestone, completion."]
  }
}
```

### 2.4 Update Badge

**Endpoint:**
```
PUT /badges/{id}
```

**Autentikasi:** Required (Superadmin only)

**Content-Type:** `multipart/form-data`

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID badge yang akan diupdate |

**Request Body:**
Sama dengan Create Badge, semua field **optional** (hanya kirim field yang ingin diupdate)

**Contoh Request (Form Data):**
```
name: Updated Badge Name
description: Updated description
icon: [file upload - optional]
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Badge updated successfully.",
  "data": {
    "id": 1,
    "code": "first_lesson",
    "name": "Updated Badge Name",
    "description": "Updated description",
    "type": "achievement",
    "threshold": 1,
    "icon_url": "https://storage.example.com/badges/updated_icon.png",
    "icon_thumb_url": "https://storage.example.com/badges/updated_icon_thumb.png",
    "created_at": "2026-02-01T00:00:00.000000Z",
    "updated_at": "2026-02-15T14:20:00.000000Z"
  }
}
```

### 2.5 Delete Badge

**Endpoint:**
```
DELETE /badges/{id}
```

**Autentikasi:** Required (Superadmin only)

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID badge yang akan dihapus |

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Badge deleted successfully.",
  "data": []
}
```

---

## 3. Level Configurations API

Endpoint untuk mengelola konfigurasi level sistem gamifikasi.

### 3.1 List All Level Configs

**Endpoint:**
```
GET /level-configs
```

**Autentikasi:** Public (tidak perlu autentikasi)

**Query Parameters (Spatie Query Builder):**
| Parameter | Type | Required | Deskripsi | Nilai yang Diizinkan |
|-----------|------|----------|-----------|---------------------|
| `search` | string | No | Full-text search menggunakan Meilisearch | Kata kunci pencarian |
| `filter[id]` | integer | No | Filter berdasarkan ID exact | ID level config |
| `filter[level]` | integer | No | Filter berdasarkan level number exact | Angka level (1, 2, 3, dst) |
| `filter[name]` | string | No | Filter berdasarkan name (partial match) | Nama level |
| `sort` | string | No | Sorting | `id`, `level`, `name`, `xp_required`, `created_at`, `updated_at` (prefix `-` untuk desc) |
| `per_page` | integer | No | Jumlah item per halaman | 1-100 (default: 15) |
| `page` | integer | No | Nomor halaman | >= 1 |

**Contoh Request:**
```bash
GET /api/v1/level-configs
GET /api/v1/level-configs?filter[level]=5
GET /api/v1/level-configs?search=beginner&sort=-xp_required
GET /api/v1/level-configs?sort=level&per_page=50
```

**Response Sukses (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "level": 1,
      "name": "Beginner",
      "xp_required": 100,
      "rewards": [
        {
          "type": "badge",
          "value": 1
        },
        {
          "type": "points",
          "value": 50
        }
      ],
      "created_at": "2026-02-01T00:00:00.000000Z",
      "updated_at": "2026-02-01T00:00:00.000000Z"
    },
    {
      "id": 2,
      "level": 2,
      "name": "Novice",
      "xp_required": 250,
      "rewards": [
        {
          "type": "badge",
          "value": 2
        }
      ],
      "created_at": "2026-02-01T00:00:00.000000Z",
      "updated_at": "2026-02-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 20,
    "per_page": 15,
    "last_page": 2
  }
}
```

### 3.2 Show Level Config Detail

**Endpoint:**
```
GET /level-configs/{id}
```

**Autentikasi:** Public (tidak perlu autentikasi)

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID level config |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "level": 1,
    "name": "Beginner",
    "xp_required": 100,
    "rewards": [
      {
        "type": "badge",
        "value": 1
      }
    ],
    "created_at": "2026-02-01T00:00:00.000000Z",
    "updated_at": "2026-02-01T00:00:00.000000Z"
  }
}
```

### 3.3 Create Level Config

**Endpoint:**
```
POST /level-configs
```

**Autentikasi:** Required (Superadmin only)

**Content-Type:** `application/json`

**Request Body:**
| Field | Type | Required | Deskripsi | Validasi | Nilai yang Diizinkan |
|-------|------|----------|-----------|----------|---------------------|
| `level` | integer | Yes | Nomor level | min:1, unique | 1, 2, 3, dst |
| `name` | string | Yes | Nama level | max:255 | "Beginner", "Novice", "Expert", dst |
| `xp_required` | integer | Yes | XP yang dibutuhkan untuk mencapai level ini | min:0 | 0, 100, 250, 500, dst |
| `rewards` | array | No | Daftar reward untuk level ini | array | Array of reward objects |
| `rewards.*.type` | string | Yes (if rewards) | Tipe reward | required_with:rewards | "badge", "points", "title", dst |
| `rewards.*.value` | mixed | Yes (if rewards) | Nilai reward | required_with:rewards | ID badge, jumlah points, dst |

**Contoh Request:**
```json
{
  "level": 1,
  "name": "Beginner",
  "xp_required": 100,
  "rewards": [
    {
      "type": "badge",
      "value": 1
    },
    {
      "type": "points",
      "value": 50
    },
    {
      "type": "title",
      "value": "Newcomer"
    }
  ]
}
```

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Level configuration created successfully.",
  "data": {
    "id": 1,
    "level": 1,
    "name": "Beginner",
    "xp_required": 100,
    "rewards": [
      {
        "type": "badge",
        "value": 1
      },
      {
        "type": "points",
        "value": 50
      }
    ],
    "created_at": "2026-02-15T10:30:00.000000Z",
    "updated_at": "2026-02-15T10:30:00.000000Z"
  }
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "level": ["The level has already been taken."],
    "xp_required": ["The xp required field must be at least 0."]
  }
}
```

### 3.4 Update Level Config

**Endpoint:**
```
PUT /level-configs/{id}
```

**Autentikasi:** Required (Superadmin only)

**Content-Type:** `application/json`

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID level config yang akan diupdate |

**Request Body:**
Sama dengan Create Level Config, semua field **optional**

**Contoh Request:**
```json
{
  "name": "Updated Beginner",
  "xp_required": 150,
  "rewards": [
    {
      "type": "badge",
      "value": 5
    }
  ]
}
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Level configuration updated successfully.",
  "data": {
    "id": 1,
    "level": 1,
    "name": "Updated Beginner",
    "xp_required": 150,
    "rewards": [
      {
        "type": "badge",
        "value": 5
      }
    ],
    "created_at": "2026-02-01T00:00:00.000000Z",
    "updated_at": "2026-02-15T14:20:00.000000Z"
  }
}
```

### 3.5 Delete Level Config

**Endpoint:**
```
DELETE /level-configs/{id}
```

**Autentikasi:** Required (Superadmin only)

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID level config yang akan dihapus |

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Level configuration deleted successfully.",
  "data": []
}
```

---

## 4. Achievements (Challenges) API

Endpoint untuk mengelola achievement/challenge gamifikasi.

### 4.1 List All Achievements

**Endpoint:**
```
GET /achievements
```

**Autentikasi:** Public (tidak perlu autentikasi)

**Query Parameters (Spatie Query Builder):**
| Parameter | Type | Required | Deskripsi | Nilai yang Diizinkan |
|-----------|------|----------|-----------|---------------------|
| `search` | string | No | Full-text search menggunakan Meilisearch | Kata kunci pencarian |
| `filter[id]` | integer | No | Filter berdasarkan ID exact | ID achievement |
| `filter[title]` | string | No | Filter berdasarkan title (partial match) | Judul achievement |
| `filter[type]` | string | No | Filter berdasarkan type exact | `daily`, `weekly`, `special` |
| `sort` | string | No | Sorting | `id`, `title`, `type`, `points_reward`, `start_at`, `end_at`, `created_at`, `updated_at` (prefix `-` untuk desc) |
| `per_page` | integer | No | Jumlah item per halaman | 1-100 (default: 15) |
| `page` | integer | No | Nomor halaman | >= 1 |

**Contoh Request:**
```bash
GET /api/v1/achievements
GET /api/v1/achievements?filter[type]=daily&sort=-points_reward
GET /api/v1/achievements?search=lesson&per_page=25
GET /api/v1/achievements?sort=start_at&filter[type]=weekly
```

**Response Sukses (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Complete 5 Lessons",
      "description": "Finish 5 lessons in any course",
      "type": "daily",
      "criteria": {
        "action": "lesson_complete",
        "target": "any_course"
      },
      "target_count": 5,
      "points_reward": 100,
      "badge_id": 1,
      "badge": {
        "id": 1,
        "code": "daily_learner",
        "name": "Daily Learner",
        "icon_url": "https://storage.example.com/badges/daily_learner.png"
      },
      "start_at": "2026-02-01T00:00:00.000000Z",
      "end_at": "2026-02-28T23:59:59.000000Z",
      "created_at": "2026-02-01T00:00:00.000000Z",
      "updated_at": "2026-02-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 30,
    "per_page": 15,
    "last_page": 2
  }
}
```

### 4.2 Show Achievement Detail

**Endpoint:**
```
GET /achievements/{id}
```

**Autentikasi:** Public (tidak perlu autentikasi)

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID achievement |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Complete 5 Lessons",
    "description": "Finish 5 lessons in any course",
    "type": "daily",
    "criteria": {
      "action": "lesson_complete"
    },
    "target_count": 5,
    "points_reward": 100,
    "badge_id": 1,
    "badge": {
      "id": 1,
      "code": "daily_learner",
      "name": "Daily Learner"
    },
    "start_at": "2026-02-01T00:00:00.000000Z",
    "end_at": "2026-02-28T23:59:59.000000Z",
    "created_at": "2026-02-01T00:00:00.000000Z",
    "updated_at": "2026-02-01T00:00:00.000000Z"
  }
}
```

### 4.3 Create Achievement

**Endpoint:**
```
POST /achievements
```

**Autentikasi:** Required (Superadmin only)

**Content-Type:** `application/json`

**Request Body:**
| Field | Type | Required | Deskripsi | Validasi | Nilai yang Diizinkan |
|-------|------|----------|-----------|----------|---------------------|
| `title` | string | Yes | Judul achievement | max:255 | "Complete 5 Lessons", "Perfect Score" |
| `description` | string | No | Deskripsi achievement | max:1000 | Penjelasan lengkap |
| `type` | string | Yes | Tipe achievement/challenge | enum | `daily`, `weekly`, `special` |
| `criteria` | object | No | Kriteria untuk menyelesaikan achievement | array/object | `{"action": "lesson_complete", "course_id": 123}` |
| `target_count` | integer | Yes | Jumlah target untuk menyelesaikan | min:1 | 1, 5, 10, 20, dst |
| `points_reward` | integer | Yes | Poin reward yang diberikan | min:0 | 50, 100, 500, dst |
| `badge_id` | integer | No | ID badge yang diberikan sebagai reward | exists:badges,id | ID badge yang valid |
| `start_at` | datetime | No | Tanggal mulai achievement aktif | date format | "2026-02-01T00:00:00" atau "2026-02-01" |
| `end_at` | datetime | No | Tanggal akhir achievement aktif | date, after:start_at | "2026-02-28T23:59:59" atau "2026-02-28" |

**Contoh Request:**
```json
{
  "title": "Complete 5 Lessons",
  "description": "Finish 5 lessons in any course within the challenge period",
  "type": "daily",
  "criteria": {
    "action": "lesson_complete",
    "target": "any_course"
  },
  "target_count": 5,
  "points_reward": 100,
  "badge_id": 1,
  "start_at": "2026-02-01T00:00:00",
  "end_at": "2026-02-28T23:59:59"
}
```

**Contoh Request Lainnya:**
```json
{
  "title": "Weekly Champion",
  "description": "Complete all weekly challenges",
  "type": "weekly",
  "criteria": {
    "action": "complete_all_weekly",
    "minimum_score": 80
  },
  "target_count": 1,
  "points_reward": 500,
  "badge_id": 5,
  "start_at": "2026-02-03",
  "end_at": "2026-02-09"
}
```

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Achievement created successfully.",
  "data": {
    "id": 1,
    "title": "Complete 5 Lessons",
    "description": "Finish 5 lessons in any course within the challenge period",
    "type": "daily",
    "criteria": {
      "action": "lesson_complete",
      "target": "any_course"
    },
    "target_count": 5,
    "points_reward": 100,
    "badge_id": 1,
    "badge": {
      "id": 1,
      "code": "daily_learner",
      "name": "Daily Learner"
    },
    "start_at": "2026-02-01T00:00:00.000000Z",
    "end_at": "2026-02-28T23:59:59.000000Z",
    "created_at": "2026-02-15T10:30:00.000000Z",
    "updated_at": "2026-02-15T10:30:00.000000Z"
  }
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "type": ["The type field must be one of: daily, weekly, special."],
    "end_at": ["The end at field must be a date after start at."],
    "badge_id": ["The selected badge id is invalid."]
  }
}
```

### 4.4 Update Achievement

**Endpoint:**
```
PUT /achievements/{id}
```

**Autentikasi:** Required (Superadmin only)

**Content-Type:** `application/json`

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID achievement yang akan diupdate |

**Request Body:**
Sama dengan Create Achievement, semua field **optional**

**Contoh Request:**
```json
{
  "title": "Updated Achievement Title",
  "description": "Updated description",
  "points_reward": 150,
  "end_at": "2026-03-31T23:59:59"
}
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Achievement updated successfully.",
  "data": {
    "id": 1,
    "title": "Updated Achievement Title",
    "description": "Updated description",
    "type": "daily",
    "criteria": {
      "action": "lesson_complete"
    },
    "target_count": 5,
    "points_reward": 150,
    "badge_id": 1,
    "start_at": "2026-02-01T00:00:00.000000Z",
    "end_at": "2026-03-31T23:59:59.000000Z",
    "created_at": "2026-02-01T00:00:00.000000Z",
    "updated_at": "2026-02-15T14:20:00.000000Z"
  }
}
```

### 4.5 Delete Achievement

**Endpoint:**
```
DELETE /achievements/{id}
```

**Autentikasi:** Required (Superadmin only)

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID achievement yang akan dihapus |

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Achievement deleted successfully.",
  "data": []
}
```

---

## 5. Categories API

Endpoint untuk mengelola kategori sistem.

### 5.1 List All Categories

**Endpoint:**
```
GET /categories
```

**Autentikasi:** Public (tidak perlu autentikasi)

**Query Parameters:**
| Parameter | Type | Required | Deskripsi | Nilai yang Diizinkan |
|-----------|------|----------|-----------|---------------------|
| `search` | string | No | Full-text search menggunakan Meilisearch | Kata kunci pencarian |
| `filter[name]` | string | No | Filter berdasarkan name (partial) | Nama kategori |
| `sort` | string | No | Sorting | `name`, `created_at`, `-name`, `-created_at` |
| `per_page` | integer | No | Jumlah item per halaman | 1-100 (default: 15) |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Programming",
      "value": "programming",
      "description": "Programming related courses",
      "status": "active",
      "created_at": "2026-02-01T00:00:00.000000Z",
      "updated_at": "2026-02-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 10,
    "per_page": 15
  }
}
```

### 5.2 Show Category Detail

**Endpoint:**
```
GET /categories/{id}
```

**Autentikasi:** Public

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Programming",
    "value": "programming",
    "description": "Programming related courses",
    "status": "active",
    "created_at": "2026-02-01T00:00:00.000000Z",
    "updated_at": "2026-02-01T00:00:00.000000Z"
  }
}
```

### 5.3 Create Category

**Endpoint:**
```
POST /categories
```

**Autentikasi:** Required (Superadmin only)

**Request Body:**
| Field | Type | Required | Validasi | Nilai yang Diizinkan |
|-------|------|----------|----------|---------------------|
| `name` | string | Yes | max:100 | Nama kategori |
| `value` | string | Yes | max:100, unique | Slug/value unik |
| `description` | string | No | max:255 | Deskripsi kategori |
| `status` | string | Yes | enum | `active`, `inactive` |

**Contoh Request:**
```json
{
  "name": "Programming",
  "value": "programming",
  "description": "Programming related courses",
  "status": "active"
}
```

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Category created successfully.",
  "data": {
    "id": 1,
    "name": "Programming",
    "value": "programming",
    "description": "Programming related courses",
    "status": "active",
    "created_at": "2026-02-15T10:30:00.000000Z",
    "updated_at": "2026-02-15T10:30:00.000000Z"
  }
}
```

### 5.4 Update Category

**Endpoint:**
```
PUT /categories/{id}
```

**Autentikasi:** Required (Superadmin only)

**Request Body:** Sama dengan Create, semua field optional

### 5.5 Delete Category

**Endpoint:**
```
DELETE /categories/{id}
```

**Autentikasi:** Required (Superadmin only)

---

## 6. Master Data API

Endpoint untuk mengelola master data sistem (data referensi).

### 6.1 List Master Data Types

**Endpoint:**
```
GET /master-data/types
```

**Autentikasi:** Public

**Response Sukses (200):**
```json
{
  "success": true,
  "data": [
    {
      "type": "course_difficulty",
      "label": "Course Difficulty Levels",
      "description": "Difficulty levels for courses",
      "is_crud_allowed": true
    },
    {
      "type": "question_types",
      "label": "Question Types",
      "description": "Available question types",
      "is_crud_allowed": false
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5
  }
}
```

### 6.2 Get Master Data by Type

**Endpoint:**
```
GET /master-data/types/{type}
```

**Autentikasi:** Required

**Path Parameters:**
| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `type` | string | Yes | Tipe master data (contoh: `course_difficulty`, `question_types`) |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "type": "course_difficulty",
    "items": [
      {
        "value": "beginner",
        "label": "Beginner",
        "sort_order": 1
      },
      {
        "value": "intermediate",
        "label": "Intermediate",
        "sort_order": 2
      },
      {
        "value": "advanced",
        "label": "Advanced",
        "sort_order": 3
      }
    ]
  }
}
```

### 6.3 List Master Data Items

**Endpoint:**
```
GET /master-data/types/{type}/items
```

**Autentikasi:** Public

**Response Sukses (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "value": "beginner",
      "label": "Beginner",
      "metadata": null,
      "is_active": true,
      "sort_order": 1,
      "created_at": "2026-02-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 3,
    "per_page": 15
  }
}
```

### 6.4 Show Master Data Item Detail

**Endpoint:**
```
GET /master-data/types/{type}/items/{id}
```

**Autentikasi:** Public

### 6.5 Create Master Data Item

**Endpoint:**
```
POST /master-data/types/{type}/items
```

**Autentikasi:** Required (Superadmin only)

**Request Body:**
| Field | Type | Required | Validasi | Nilai yang Diizinkan |
|-------|------|----------|----------|---------------------|
| `value` | string | Yes | max:100 | Nilai unik item |
| `label` | string | Yes | max:255 | Label yang ditampilkan |
| `metadata` | object | No | array | Data tambahan dalam format JSON |
| `is_active` | boolean | No | boolean | `true`, `false` (default: true) |
| `sort_order` | integer | No | min:0 | Urutan sorting (default: 0) |

**Contoh Request:**
```json
{
  "value": "expert",
  "label": "Expert",
  "metadata": {
    "description": "For expert level learners",
    "color": "#FF5733"
  },
  "is_active": true,
  "sort_order": 4
}
```

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Master data created successfully.",
  "data": {
    "id": 4,
    "value": "expert",
    "label": "Expert",
    "metadata": {
      "description": "For expert level learners",
      "color": "#FF5733"
    },
    "is_active": true,
    "sort_order": 4,
    "created_at": "2026-02-15T10:30:00.000000Z"
  }
}
```

### 6.6 Update Master Data Item

**Endpoint:**
```
PUT /master-data/types/{type}/items/{id}
```

**Autentikasi:** Required (Superadmin only)

**Request Body:** Sama dengan Create, semua field optional

### 6.7 Delete Master Data Item

**Endpoint:**
```
DELETE /master-data/types/{type}/items/{id}
```

**Autentikasi:** Required (Superadmin only)

---

## Response Format Standar

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Pagination Response
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "to": 15,
    "total": 100,
    "per_page": 15,
    "last_page": 7
  }
}
```

### Error Responses

**401 Unauthorized**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**403 Forbidden**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

**404 Not Found**
```json
{
  "success": false,
  "message": "Resource not found."
}
```

**422 Validation Error**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "field_name": [
      "Error message 1",
      "Error message 2"
    ]
  }
}
```

**500 Internal Server Error**
```json
{
  "success": false,
  "message": "Internal server error."
}
```

---

## Catatan Penting

### Query Builder Features
- **Sorting**: Gunakan `-` prefix untuk descending (contoh: `sort=-created_at`)
- **Filtering**: 
  - Exact match: `filter[type]=value`
  - Partial match: `filter[name]=value` (untuk field yang mendukung)
  - Search: `search=keyword` (Meilisearch full-text search)

### Otorisasi
- **GET endpoints**: Public access (tidak perlu login)
- **POST/PUT/DELETE endpoints**: Hanya **Superadmin**

### File Upload
- **Badges icon**: Max 2MB, format: JPEG, PNG, SVG, WebP
- Upload menggunakan `multipart/form-data`

### Date Format
- Input: `Y-m-d` atau `Y-m-d\TH:i:s` (contoh: `2026-02-15` atau `2026-02-15T10:30:00`)
- Output: ISO 8601 dengan timezone UTC (contoh: `2026-02-15T10:30:00.000000Z`)

### Pagination
- Default: 15 items per page
- Max: 100 items per page
- Min: 1 item per page
