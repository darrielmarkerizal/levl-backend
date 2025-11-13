# Analisis Database - Duplikasi, Kolom Tidak Berguna, dan Normalisasi

## 🔴 Masalah yang Ditemukan

### 1. **DUPLIKASI: Tabel Audit Logging**

**Masalah:** Ada 2 tabel yang memiliki fungsi serupa untuk audit logging:
- `audit_logs` (Modules/Common)
- `system_audits` (Modules/Operations)

**Detail:**
- **audit_logs**: 
  - Kolom: `event`, `target_type`, `target_id`, `actor_type`, `actor_id`, `user_id`, `properties`, `logged_at`
  - Polimorfik dengan `target_type` dan `actor_type`
  
- **system_audits**:
  - Kolom: `action`, `user_id`, `module`, `target_table`, `target_id`, `ip_address`, `user_agent`, `meta`, `logged_at`
  - Lebih spesifik dengan `module` dan `target_table`

**Rekomendasi:**
- **Pilih salah satu** atau **gabungkan** menjadi satu tabel
- Jika tetap terpisah, pastikan ada pembagian fungsi yang jelas:
  - `audit_logs`: untuk audit umum aplikasi
  - `system_audits`: untuk audit sistem/administratif
- Atau buat satu tabel `audits` yang lebih fleksibel dengan kolom opsional

---

### 2. **DUPLIKASI: Kolom `progress_percent`**

**Masalah:** Kolom `progress_percent` ada di 2 tabel yang saling terkait:
- `enrollments.progress_percent`
- `course_progress.progress_percent`

**Detail:**
- `enrollments` memiliki `progress_percent` (float, default 0)
- `course_progress` juga memiliki `progress_percent` (float, default 0)
- `course_progress` memiliki relasi `enrollment_id` ke `enrollments`
- Keduanya diupdate oleh `ProgressionService`

**Rekomendasi:**
- **Hapus `progress_percent` dari `enrollments`** karena:
  - `course_progress` sudah menyimpan progress course secara spesifik
  - `enrollments` hanya perlu menyimpan status enrollment
  - Progress bisa dihitung dari `course_progress` atau diambil langsung
- Atau **hapus tabel `course_progress`** jika `enrollments.progress_percent` sudah cukup
- **Pilihan terbaik:** Hapus dari `enrollments`, gunakan `course_progress` sebagai source of truth

---

### 3. **DUPLIKASI: Kolom `score` di Submissions dan Grades**

**Masalah:** Score disimpan di 2 tempat:
- `submissions.score` (integer, nullable)
- `grades.score` (decimal 8,2, default 0)

**Detail:**
- `submissions` memiliki `score` dan `feedback`
- `grades` memiliki `score`, `max_score`, `feedback`, dan `status`
- `grades` menggunakan polimorfik `source_type` dan `source_id` (bisa assignment atau attempt)

**Rekomendasi:**
- **Hapus `score` dan `feedback` dari `submissions`** karena:
  - `grades` sudah menyimpan semua informasi grading secara terpusat
  - `grades` lebih fleksibel dengan polimorfik relationship
  - Menghindari inkonsistensi data
- Atau **hapus `grades`** jika hanya untuk assignment (tapi `grades` juga untuk attempts)
- **Pilihan terbaik:** Hapus dari `submissions`, gunakan `grades` sebagai source of truth untuk semua grading

---

### 4. **DUPLIKASI: Kolom `course_id` di `course_progress`**

**Masalah:** `course_progress` memiliki `course_id` padahal sudah ada `enrollment_id` yang sudah memiliki relasi ke `course_id` melalui `enrollments`.

**Detail:**
- `course_progress.enrollment_id` → `enrollments.course_id`
- `course_progress.course_id` (redundant)

**Rekomendasi:**
- **Hapus `course_id` dari `course_progress`** karena:
  - Bisa diakses via `enrollment.course_id`
  - Mengurangi redundansi data
  - Memudahkan maintenance
- Jika perlu untuk performa query, gunakan index atau eager loading

---

### 5. **KOLOM TIDAK BERGUNA: `category` di `courses` (Sudah Dihapus)**

**Status:** ✅ Sudah diperbaiki
- Kolom `category` (string) sudah dihapus dari `courses`
- Diganti dengan `category_id` (foreign key) ke tabel `categories`
- Migration: `2025_11_04_000001_drop_category_column_from_courses.php`

---

### 6. **PELUANG NORMALISASI: JSON Fields di `courses`**

**Masalah:** Beberapa field menggunakan JSON yang bisa dinormalisasi:
- `tags_json` → Sudah ada tabel `tags` dan `course_tag` pivot
- `outcomes_json` → Bisa dibuat tabel `course_outcomes`
- `prereq_text` → Bisa dibuat tabel `course_prerequisites`

**Detail:**
- `tags_json` sudah ada solusi dengan tabel `tags` dan pivot `course_tag`
- `outcomes_json` dan `prereq_text` masih menggunakan JSON

**Rekomendasi:**
- **`tags_json`**: Hapus kolom ini, gunakan relasi `tags` via pivot table
- **`outcomes_json`**: Buat tabel `course_outcomes` dengan kolom:
  - `id`, `course_id`, `outcome_text`, `order`, `timestamps`
- **`prereq_text`**: Buat tabel `course_prerequisites` dengan kolom:
  - `id`, `course_id`, `prerequisite_course_id`, `is_required`, `timestamps`

**Keuntungan:**
- Lebih mudah untuk query dan filter
- Bisa di-index untuk performa
- Lebih mudah untuk maintenance
- Mendukung relasi yang lebih kompleks

---

### 7. **PELUANG NORMALISASI: Status Enum yang Berulang**

**Masalah:** Beberapa tabel menggunakan enum status yang mirip:
- `enrollments.status`: `['pending', 'active', 'completed', 'cancelled']`
- `course_progress.status`: `['not_started', 'in_progress', 'completed']`
- `unit_progress.status`: `['not_started', 'in_progress', 'completed']`
- `lesson_progress.status`: `['not_started', 'in_progress', 'completed']`

**Rekomendasi:**
- **Standarisasi status** untuk progress tables:
  - Gunakan status yang sama: `['not_started', 'in_progress', 'completed']` untuk semua progress
  - Atau buat tabel `status_types` jika perlu fleksibilitas
- **Untuk enrollments**: Status berbeda karena konteks berbeda (enrollment vs progress)

---

### 8. **DUPLIKASI: Timestamp Fields**

**Masalah:** Beberapa tabel memiliki timestamp yang mirip:
- `enrollments`: `enrolled_at`, `completed_at`
- `course_progress`: `started_at`, `completed_at`
- `unit_progress`: `started_at`, `completed_at`
- `lesson_progress`: `started_at`, `completed_at`

**Rekomendasi:**
- **Konsisten dengan naming**: Gunakan `started_at` dan `completed_at` untuk semua progress tables
- **Untuk enrollments**: `enrolled_at` sudah tepat karena berbeda konteks

---

## ✅ Rekomendasi Prioritas

### **PRIORITAS TINGGI (Perbaiki Segera)**

1. **Hapus `progress_percent` dari `enrollments`**
   - Migration: Drop column `progress_percent` dari `enrollments`
   - Update `ProgressionService` untuk tidak update `enrollments.progress_percent`
   - Update semua query yang menggunakan `enrollments.progress_percent`

2. **Hapus `score` dan `feedback` dari `submissions`**
   - Migration: Drop columns `score` dan `feedback` dari `submissions`
   - Update `SubmissionService` untuk tidak menyimpan score di submissions
   - Pastikan semua grading menggunakan `grades` table

3. **Hapus `course_id` dari `course_progress`**
   - Migration: Drop column `course_id` dari `course_progress`
   - Update query untuk menggunakan `enrollment.course_id`

### **PRIORITAS SEDANG (Perbaiki dalam Sprint Berikutnya)**

4. **Gabungkan atau Pisahkan `audit_logs` dan `system_audits`**
   - Tentukan strategi: gabung atau pisah dengan jelas
   - Buat migration untuk konsolidasi

5. **Normalisasi `outcomes_json` dan `prereq_text`**
   - Buat tabel `course_outcomes` dan `course_prerequisites`
   - Migrate data dari JSON ke tabel
   - Hapus kolom JSON

### **PRIORITAS RENDAH (Perbaiki Saat Refactoring)**

6. **Standarisasi status enum**
   - Review semua status enum
   - Buat dokumentasi status yang digunakan
   - Pertimbangkan tabel `status_types` jika perlu

7. **Konsisten timestamp naming**
   - Review semua timestamp fields
   - Standarisasi naming convention

---

## 📊 Ringkasan

| Kategori | Jumlah | Status |
|----------|--------|--------|
| Duplikasi Tabel | 1 | 🔴 Perlu Perbaikan |
| Duplikasi Kolom | 4 | 🔴 Perlu Perbaikan |
| Kolom Tidak Berguna | 1 | ✅ Sudah Diperbaiki |
| Peluang Normalisasi | 3 | 🟡 Bisa Diperbaiki |

---

## 🔧 Migration Scripts yang Diperlukan

1. `remove_progress_percent_from_enrollments.php`
2. `remove_score_feedback_from_submissions.php`
3. `remove_course_id_from_course_progress.php`
4. `normalize_course_outcomes.php`
5. `normalize_course_prerequisites.php`
6. `consolidate_audit_tables.php` (jika diperlukan)

---

## 📝 Catatan

- Semua perubahan harus melalui migration
- Pastikan backup database sebelum migration
- Update semua model, service, dan repository yang terkait
- Update test cases untuk mencerminkan perubahan struktur
- Dokumentasikan perubahan di CHANGELOG

