# Test Updates - Database Normalization

## Overview

Dokumen ini menjelaskan perubahan yang dilakukan pada test cases setelah database normalization.

## Test Files yang Diupdate

### 1. `EnrollmentFactory.php`
**Perubahan:**
- ✅ Hapus `progress_percent` dari `definition()`
- ✅ Hapus `progress_percent` dari `completed()` state

**Alasan:** Progress sekarang disimpan di `course_progress` table, bukan di `enrollments`.

---

### 2. `SubmissionServiceTest.php`
**Perubahan:**
- ✅ Update test `grade applies late penalty` untuk menggunakan `$graded->grade->score` dan `$graded->grade->feedback`
- ✅ Update test `grade uses assignment late penalty` untuk menggunakan `$graded->grade->score`

**Sebelum:**
```php
expect($graded->score)->toEqual(80);
expect($graded->feedback)->toEqual('Good work');
```

**Sesudah:**
```php
expect($graded->grade)->not->toBeNull();
expect($graded->grade->score)->toEqual(80);
expect($graded->grade->feedback)->toEqual('Good work');
```

**Alasan:** Score dan feedback sekarang disimpan di `grades` table, bukan di `submissions`.

---

### 3. `SubmissionCrudTest.php`
**Perubahan:**
- ✅ Update test `admin can grade submission` untuk assert ke `grades` table
- ✅ Update test `cannot update graded submission` untuk create grade record

**Sebelum:**
```php
assertDatabaseHas('submissions', [
    'id' => $submission->id,
    'status' => 'graded',
    'score' => 85,
]);
```

**Sesudah:**
```php
assertDatabaseHas('submissions', [
    'id' => $submission->id,
    'status' => 'graded',
]);

assertDatabaseHas('grades', [
    'source_type' => 'assignment',
    'source_id' => $this->assignment->id,
    'user_id' => $this->student->id,
    'score' => 85,
    'feedback' => 'Good work!',
]);
```

**Alasan:** Score dan feedback sekarang disimpan di `grades` table.

---

### 4. `SystemAuditTest.php`
**Perubahan:**
- ✅ Update untuk menggunakan `Audit` model dengan `context='system'`
- ✅ Update assertion untuk menggunakan tabel `audits` bukan `system_audits`
- ✅ Tambahkan test untuk scope `system()`

**Sebelum:**
```php
use Modules\Operations\Models\SystemAudit;

$audit = SystemAudit::create([...]);
assertDatabaseHas('system_audits', [...]);
```

**Sesudah:**
```php
use Modules\Common\Models\Audit;

$audit = Audit::create([
    'action' => 'create',
    'context' => 'system',
    ...
]);
assertDatabaseHas('audits', [
    'context' => 'system',
    ...
]);
```

**Alasan:** Tabel `system_audits` dan `audit_logs` sudah digabung menjadi `audits`.

---

### 5. `CourseCrudTest.php`
**Perubahan:**
- ✅ Tambahkan test untuk create course dengan outcomes dan prerequisites
- ✅ Tambahkan test untuk update course outcomes dan prerequisites

**Test Baru:**
```php
it('admin can create course with outcomes and prerequisites', function () {
    $prerequisiteCourse = Course::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            ...
            'outcomes' => [
                'Learn Laravel basics',
                'Understand MVC pattern',
            ],
            'prereq' => [$prerequisiteCourse->id],
        ]);

    expect($course->outcomes)->toHaveCount(2);
    expect($course->prerequisiteCourses)->toHaveCount(1);
});
```

**Alasan:** Outcomes dan prerequisites sekarang disimpan di tabel terpisah, bukan JSON.

---

## Controller Updates

### 1. `SubmissionController.php`
**Perubahan:**
- ✅ Tambahkan `'grade'` ke eager loading di `index()` dan `show()`

**Alasan:** Untuk memastikan grade relationship ter-load saat return submission data.

---

### 2. `CourseController.php`
**Perubahan:**
- ✅ Tambahkan `'outcomes'` dan `'prerequisiteCourses'` ke eager loading di `show()`

**Alasan:** Untuk return outcomes dan prerequisites saat get course detail.

---

## Service Updates

### 1. `SubmissionService.php`
**Perubahan:**
- ✅ Tambahkan `'grade'` ke `fresh()` calls

**Alasan:** Untuk memastikan grade relationship ter-load setelah create/update.

---

### 2. `CourseService.php`
**Perubahan:**
- ✅ Tambahkan method `syncCourseOutcomes()` untuk sync outcomes
- ✅ Tambahkan method `syncCoursePrerequisites()` untuk sync prerequisites
- ✅ Update `create()` dan `update()` untuk handle outcomes dan prerequisites
- ✅ Tambahkan `'outcomes'` dan `'prerequisiteCourses'` ke `fresh()` calls

**Alasan:** Outcomes dan prerequisites sekarang disimpan di tabel terpisah, perlu sync logic.

---

## Request Validation Updates

### 1. `HasSchemesRequestRules.php`
**Perubahan:**
- ✅ Update validation rule untuk `prereq.*` dari `'string'` ke `'integer', 'exists:courses,id'`
- ✅ Update error message untuk prerequisites

**Alasan:** Prerequisites sekarang berupa array of course IDs, bukan strings.

---

### 2. `CourseRequest.php`
**Perubahan:**
- ✅ Update `validated()` untuk map `prereq` ke `prerequisites`
- ✅ Hapus mapping `outcomes` ke `outcomes_json`

**Alasan:** Outcomes dan prerequisites sekarang di-handle sebagai array, bukan JSON.

---

## Model Accessor Behavior

### Submission Model
- ✅ `$submission->score` - Accessor yang mengambil dari `grade->score`
- ✅ `$submission->feedback` - Accessor yang mengambil dari `grade->feedback`
- ✅ `$submission->graded_at` - Accessor yang mengambil dari `grade->graded_at`

**Note:** Accessor ini akan otomatis load grade relationship jika belum ter-load.

### CourseProgress Model
- ✅ `$courseProgress->course_id` - Accessor yang mengambil dari `enrollment->course_id`

**Note:** Accessor ini akan otomatis load enrollment relationship jika belum ter-load.

---

## Testing Best Practices

### 1. Eager Loading
Selalu eager load relationships yang diperlukan:
```php
$submission->load('grade');
$course->load(['outcomes', 'prerequisiteCourses']);
```

### 2. Database Assertions
Assert ke tabel yang benar:
- ✅ Score/feedback → `grades` table
- ✅ Progress → `course_progress` table
- ✅ Outcomes → `course_outcomes` table
- ✅ Prerequisites → `course_prerequisites` table
- ✅ Audit logs → `audits` table

### 3. Factory Updates
Pastikan factory tidak menggunakan kolom yang sudah dihapus:
- ✅ `EnrollmentFactory` - tidak ada `progress_percent`
- ✅ `SubmissionFactory` - tidak ada `score`, `feedback`, `graded_at`

---

## Migration Impact on Tests

Setelah migration, pastikan:
1. ✅ Semua test yang menggunakan `enrollment->progress_percent` diupdate
2. ✅ Semua test yang menggunakan `submission->score` atau `submission->feedback` diupdate
3. ✅ Semua test yang menggunakan `course->outcomes_json` atau `course->prereq_text` diupdate
4. ✅ Semua test yang menggunakan `SystemAudit` atau `AuditLog` diupdate ke `Audit`
5. ✅ Semua factory diupdate untuk tidak menggunakan kolom yang dihapus

---

## Running Tests

Setelah semua update, jalankan test:
```bash
php artisan test
```

Pastikan semua test pass sebelum deploy.

