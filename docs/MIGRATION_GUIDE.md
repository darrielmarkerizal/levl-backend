# Migration Guide - Database Normalization

## Overview

Dokumen ini menjelaskan perubahan database yang dilakukan untuk menghilangkan duplikasi, kolom tidak berguna, dan normalisasi struktur database.

## Migration Files

### 1. Consolidate Audit Tables (`2025_11_13_052646_consolidate_audit_tables_into_audits_table.php`)

**Tujuan:** Menggabungkan `audit_logs` dan `system_audits` menjadi satu tabel `audits`.

**Perubahan:**
- Membuat tabel `audits` baru dengan kolom lengkap
- Migrasi data dari `audit_logs` dan `system_audits` ke `audits`
- Menghapus tabel `audit_logs` dan `system_audits`

**Kolom Baru:**
- `action`: Enum untuk semua jenis aksi
- `actor_type`, `actor_id`: Polimorfik untuk actor
- `target_table`, `target_type`, `target_id`: Polimorfik untuk target
- `module`: Nama modul
- `context`: 'system' atau 'application'
- `ip_address`, `user_agent`: Metadata request
- `meta`, `properties`: JSON untuk data tambahan

**Cara Menggunakan:**
```php
use Modules\Common\Services\AuditService;

$auditService = app(AuditService::class);

// Log application audit
$auditService->logApplication('create', $course, [
    'module' => 'Schemes',
    'properties' => ['title' => $course->title],
]);

// Log system audit
$auditService->logSystem('error', null, [
    'module' => 'System',
    'meta' => ['error' => 'Database connection failed'],
]);
```

---

### 2. Remove Progress Percent from Enrollments (`2025_11_13_052651_remove_progress_percent_from_enrollments.php`)

**Tujuan:** Menghapus `progress_percent` dari `enrollments` karena sudah ada di `course_progress`.

**Perubahan:**
- Migrasi data `progress_percent` dari `enrollments` ke `course_progress`
- Menghapus kolom `progress_percent` dari `enrollments`
- Menghapus index `['status', 'progress_percent']` dan membuat index `['status']` baru

**Cara Menggunakan:**
```php
// Sebelum (TIDAK LAGI BERLAKU):
$enrollment->progress_percent; // ❌

// Sesudah:
$enrollment->courseProgress->progress_percent; // ✅
// atau
$enrollment->courseProgress()->value('progress_percent'); // ✅
```

---

### 3. Remove Score and Feedback from Submissions (`2025_11_13_052656_remove_score_feedback_from_submissions.php`)

**Tujuan:** Menghapus `score` dan `feedback` dari `submissions` karena sudah ada di `grades`.

**Perubahan:**
- Migrasi data `score` dan `feedback` dari `submissions` ke `grades`
- Menghapus kolom `score`, `feedback`, dan `graded_at` dari `submissions`

**Cara Menggunakan:**
```php
// Sebelum (TIDAK LAGI BERLAKU):
$submission->score; // ❌
$submission->feedback; // ❌

// Sesudah:
$submission->grade->score; // ✅
$submission->grade->feedback; // ✅
// atau menggunakan accessor (otomatis load grade):
$submission->score; // ✅ (via accessor)
$submission->feedback; // ✅ (via accessor)
```

**Update Service:**
- `SubmissionService::grade()` sekarang membuat/update record di `grades` table
- Submission hanya update `status` menjadi 'graded'

---

### 4. Remove Course ID from Course Progress (`2025_11_13_052659_remove_course_id_from_course_progress.php`)

**Tujuan:** Menghapus `course_id` dari `course_progress` karena bisa diakses via `enrollment.course_id`.

**Perubahan:**
- Menghapus kolom `course_id` dari `course_progress`
- Update relasi `course()` di model untuk menggunakan `hasOneThrough`

**Cara Menggunakan:**
```php
// Sebelum (TIDAK LAGI BERLAKU):
$courseProgress->course_id; // ❌

// Sesudah:
$courseProgress->course_id; // ✅ (via accessor, mengambil dari enrollment)
$courseProgress->course; // ✅ (via relationship)
$courseProgress->enrollment->course_id; // ✅ (langsung)
```

---

### 5. Normalize Course Outcomes and Prerequisites (`2025_11_13_052704_normalize_course_outcomes_and_prerequisites.php`)

**Tujuan:** Menormalisasi `outcomes_json` dan `prereq_text` ke tabel terpisah.

**Perubahan:**
- Membuat tabel `course_outcomes` dengan kolom: `id`, `course_id`, `outcome_text`, `order`
- Membuat tabel `course_prerequisites` dengan kolom: `id`, `course_id`, `prerequisite_course_id`, `is_required`
- Migrasi data dari JSON ke tabel
- Menghapus kolom `outcomes_json` dan `prereq_text` dari `courses`

**Cara Menggunakan:**
```php
// Sebelum (TIDAK LAGI BERLAKU):
$course->outcomes_json; // ❌
$course->prereq_text; // ❌

// Sesudah:
$course->outcomes; // ✅ Collection of CourseOutcome
$course->prerequisites; // ✅ Collection of CoursePrerequisite
$course->prerequisiteCourses; // ✅ Collection of Course (via belongsToMany)

// Contoh:
foreach ($course->outcomes as $outcome) {
    echo $outcome->outcome_text;
}

foreach ($course->prerequisiteCourses as $prereq) {
    echo $prereq->title;
}
```

---

## Model Updates

### Course Model
- ✅ Menghapus `outcomes_json` dan `prereq_json` dari `$fillable` dan `$casts`
- ✅ Menambahkan relasi `outcomes()`, `prerequisites()`, `requiredBy()`, `prerequisiteCourses()`

### Enrollment Model
- ✅ Menghapus `progress_percent` dari `$fillable` dan `$casts`

### CourseProgress Model
- ✅ Menghapus `course_id` dari `$fillable`
- ✅ Update relasi `course()` menggunakan `hasOneThrough`
- ✅ Menambahkan accessor `getCourseIdAttribute()`

### Submission Model
- ✅ Menghapus `score`, `feedback`, `graded_at` dari `$fillable` dan `$casts`
- ✅ Menambahkan relasi `grade()`
- ✅ Menambahkan accessor `getScoreAttribute()`, `getFeedbackAttribute()`, `getGradedAtAttribute()`

### Grade Model
- ✅ Update relasi `source()` untuk merujuk ke `Assignment` (bukan `Submission`)
- ✅ Menambahkan method `submission()` untuk mendapatkan submission terkait

---

## Service Updates

### SubmissionService
- ✅ Update method `grade()` untuk membuat/update record di `grades` table
- ✅ Submission hanya update `status` menjadi 'graded'

### ProgressionService
- ✅ Menghapus update `enrollment->progress_percent`
- ✅ Update `course_progress` query untuk tidak menggunakan `course_id`

### EnrollmentService
- ✅ Menghapus inisialisasi `progress_percent` saat create enrollment

### InitializeProgressForEnrollment Listener
- ✅ Menghapus update `enrollment->progress_percent`
- ✅ Update `ensureCourseProgressExists()` untuk tidak menggunakan `course_id`

---

## Config File

### `config/status.php`
File baru untuk registry semua status enum:
```php
config('status.enrollment.active')
config('status.progress.completed')
config('status.course.published')
// dll
```

---

## Testing

Setelah migration, pastikan untuk:
1. ✅ Update semua test yang menggunakan `enrollment->progress_percent`
2. ✅ Update semua test yang menggunakan `submission->score` atau `submission->feedback`
3. ✅ Update semua test yang menggunakan `course_progress->course_id`
4. ✅ Update semua test yang menggunakan `course->outcomes_json` atau `course->prereq_text`
5. ✅ Update factory untuk tidak menggunakan kolom yang dihapus

---

## Rollback

Semua migration memiliki method `down()` untuk rollback jika diperlukan. Pastikan backup database sebelum rollback.

---

## Notes

- Semua perubahan backward compatible melalui accessor di model
- Progress dihitung dari `course_progress` table
- Score dan feedback diambil dari `grades` table via relationship
- Course ID di `course_progress` diakses via `enrollment.course_id`

