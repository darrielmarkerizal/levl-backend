# Dokumentasi API Modul Pembelajaran

**Base URL**: `/api/v1`

---

## 🔐 Matriks Otorisasi

| Peran       | Lihat | Buat/Edit | Hapus | Publikasi | Kirim | Nilai |
|------------|------|-------------|--------|---------|--------|-------|
| **Siswa**| ✅   | ❌          | ❌     | ❌      | ✅     | ❌    |
| **Instruktur**| ✅| ✅ (Milik Sendiri)    | ✅ (Milik Sendiri)| ✅ (Milik Sendiri)| ❌     | ✅ (Milik Sendiri)|
| **Admin**  | ✅   | ✅          | ✅     | ✅      | ❌     | ✅    |

---

## 📝 Tugas (Assignments)

### 1. Daftar Tugas (Berdasarkan Kursus)
Mengambil daftar tugas yang dipaginasi untuk kursus tertentu.

**Endpoint:** `GET /courses/{course_slug}/assignments`

**Parameter Query:**

| Parameter | Tipe | Deskripsi | Nilai yang Tersedia |
|-----------|------|-------------|-----------------|
| `filter[status]` | string | Filter berdasarkan status | `draft` (Draf)<br>`published` (Dipublikasi)<br>`archived` (Diarsipkan) |
| `filter[submission_type]` | string | Filter berdasarkan tipe pengumpulan | `text` (Teks saja)<br>`file` (File saja)<br>`mixed` (Teks & File) |
| `sort` | string | Urutkan hasil | `-created_at` (Terbaru)<br>`title` (Judul A-Z)<br>`deadline_at` (Tenggat waktu) |
| `include` | csv | Sertakan relasi | `questions` (Soal-soal)<br>`lesson` (Pelajaran)<br>`creator` (Pembuat) |
| `page` | int | Nomor halaman | Default: 1 |
| `per_page` | int | Item per halaman | Default: 15 |

**Contoh Respons:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Kuis 1",
      "status": "published",
      "lesson": { "id": 101, "title": "Pengenalan" }
    }
  ],
  "meta": { "total": 50, "per_page": 15 }
}
```

---

### 2. Daftar Tugas Belum Dikerjakan (Berdasarkan Kursus)
Mengambil daftar tugas yang belum dikerjakan/diserahkan oleh siswa untuk kursus tertentu.

**Endpoint:** `GET /courses/{course_slug}/assignments/incomplete`

> [!INFO]
> **HANYA UNTUK SISWA:** Endpoint ini menampilkan assignment yang:
> - Status: **Published** (Dipublikasi)
> - Belum ada submission dengan status `submitted` atau `graded` dari siswa yang sedang login

**Parameter Query:**

| Parameter | Tipe | Deskripsi | Nilai yang Tersedia |
|-----------|------|-------------|-----------------|
| `filter[status]` | string | Filter berdasarkan status | `published` (Dipublikasi) |
| `filter[submission_type]` | string | Filter berdasarkan tipe pengumpulan | `text` (Teks saja)<br>`file` (File saja)<br>`mixed` (Teks & File) |
| `sort` | string | Urutkan hasil | `-created_at` (Terbaru)<br>`title` (Judul A-Z)<br>`deadline_at` (Tenggat waktu) |
| `include` | csv | Sertakan relasi | `questions` (Soal-soal)<br>`lesson` (Pelajaran)<br>`creator` (Pembuat) |
| `page` | int | Nomor halaman | Default: 1 |
| `per_page` | int | Item per halaman | Default: 15 |

**Contoh Request:**
```
GET /api/v1/courses/junior-web-programmer/assignments/incomplete?page=1&per_page=15&sort=-deadline_at
```

**Contoh Respons:**
```json
{
  "success": true,
  "message": "messages.assignments.incomplete_list_retrieved",
  "data": [
    {
      "id": 5,
      "title": "Kuis Laravel Controllers",
      "status": "published",
      "deadline_at": "2026-02-01T23:59:59Z",
      "lesson": { "id": 101, "title": "Laravel Controllers" }
    },
    {
      "id": 8,
      "title": "Project Mini Laravel Routing",
      "status": "published",
      "deadline_at": "2026-02-15T23:59:59Z",
      "lesson": { "id": 102, "title": "Laravel Routing" }
    }
  ],
  "meta": { 
    "total": 12, 
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

---

### 3. Buat Tugas

**Endpoint:** `POST /assignments`

**Body (JSON):**

| Field | Tipe | Wajib | Deskripsi | Nilai yang Tersedia |
|-------|------|----------|-------------|---------------------|
| `title` | string | Ya | Judul tugas | - |
| `description` | string | Tidak | Instruksi/deskripsi tugas | - |
| `assignable_type` | string | Ya | Jenis cakupan tugas | `Course` (Kursus)<br>`Unit` (Unit)<br>`Lesson` (Pelajaran) |
| `assignable_slug` | string | Ya | Slug dari cakupan (course/unit/lesson) | - |
| `submission_type` | enum | Ya | Tipe pengumpulan | `text` (Siswa kirim teks)<br>`file` (Siswa upload file)<br>`mixed` (Teks & file) |
| `max_score` | int | Tidak | Skor maksimal | Default: 100 |
| `available_from` | datetime | Tidak | Waktu mulai tersedia | Format: ISO 8601<br>Contoh: `2024-03-01 08:00:00` |
| `deadline_at` | datetime | Tidak | Batas waktu pengumpulan | Format: ISO 8601<br>Contoh: `2024-03-07 23:59:59` |
| `tolerance_minutes` | int | Tidak | Toleransi keterlambatan (menit) | Contoh: 15, 30, 60 |
| `time_limit_minutes` | int | Tidak | Batas waktu pengerjaan per percobaan (menit). Jika diisi, server membatasi pengerjaan sesuai timer dengan toleransi jaringan 60 detik. | Contoh: 30, 60, 120 |
| `late_penalty_percent` | int | Tidak | Penalti terlambat (persen) | Nilai: 0-100<br>Contoh: 20 = potongan 20% |
| `max_attempts` | int | Tidak | Maksimal percobaan | `null` = tidak terbatas<br>Contoh: 1, 2, 3 |
| `cooldown_minutes` | int | Tidak | Jeda waktu antar percobaan (menit) | Contoh: 30, 60, 120 |
| `retake_enabled` | bool | Tidak | Izinkan mengulang | `true` atau `false` |
| `review_mode` | enum | Tidak | Mode peninjauan hasil | `immediate` (Tampil langsung)<br>`deferred` (Tampil setelah deadline)<br>`hidden` (Tidak ditampilkan otomatis) |
| `randomization_type` | enum | Tidak | Tipe pengacakan soal | `static` (Urutan tetap)<br>`random_order` (Acak urutan)<br>`bank` (Pilih subset acak) |
| `question_bank_count` | int | **Wajib jika `randomization_type` = `bank`** | Jumlah soal yang dipilih dari bank | Harus ≤ total soal di assignment<br>Contoh: 20, 50 |
| `status` | enum | Tidak | Status publikasi | `draft` (Draf)<br>`published` (Dipublikasi) |
| `attachments` | array | Tidak | File lampiran | Maksimal 5 file |

---

#### Contoh Request (Skenario Lengkap)

### 📘 Tipe: LESSON (Pelajaran)

**1. Kuis Pelajaran Laravel Controllers (Standar)**
*Lesson | Mixed | Urutan Acak | 3 Percobaan*

Kuis standar setelah pelajaran Laravel Controllers dengan urutan soal diacak.

```json
{
  "title": "Kuis Laravel Controllers",
  "description": "Kuis untuk menguji pemahaman tentang controller di Laravel.",
  "assignable_type": "Lesson",
  "assignable_slug": "laravel-controllers",
  "submission_type": "mixed",
  "max_score": 100,
  "available_from": "2026-01-25 08:00:00",
  "deadline_at": "2026-01-31 23:59:59",
  "tolerance_minutes": 15,
  "max_attempts": 3,
  "cooldown_minutes": 60,
  "retake_enabled": true,
  "review_mode": "deferred",
  "randomization_type": "random_order",
  "status": "published"
}
```

**2. Latihan Laravel Routing (Bank Soal)**
*Lesson | Mixed | Bank Soal 15 dari 30 | Langsung Tampil Hasil*

Latihan dengan sistem bank soal - setiap siswa mendapat 15 soal acak dari 30 soal yang tersedia.

```json
{
  "title": "Latihan Laravel Routing",
  "description": "Latihan routing Laravel. Setiap siswa akan mendapat 15 soal berbeda dari bank soal.",
  "assignable_type": "Lesson",
  "assignable_slug": "laravel-routing",
  "submission_type": "mixed",
  "max_score": 75,
  "available_from": "2026-01-23 08:00:00",
  "deadline_at": "2026-01-30 23:59:59",
  "tolerance_minutes": 30,
  "max_attempts": 2,
  "review_mode": "immediate",
  "randomization_type": "bank",
  "question_bank_count": 15,
  "status": "published"
}
```

**3. Refleksi Harian Introduction to Laravel (Teks Saja)**
*Lesson | Text | Bobot Rendah | Tidak Terbatas*

Tugas refleksi sederhana untuk merangkum pembelajaran.

```json
{
  "title": "Refleksi: Introduction to Laravel",
  "description": "Tuliskan 3 hal penting yang Anda pelajari hari ini dalam 100-150 kata.",
  "assignable_type": "Lesson",
  "assignable_slug": "introduction-to-laravel",
  "submission_type": "text",
  "max_score": 10,
  "deadline_at": "2026-01-24 23:59:59",
  "review_mode": "immediate",
  "status": "published"
}
```

**4. Praktikum Laravel Controllers (File Upload)**
*Lesson | File | Penalti Terlambat 25% | Toleransi 1 Jam*

Upload hasil praktikum membuat controller. Ada penalti untuk keterlambatan.

```http
POST /api/v1/assignments
Content-Type: multipart/form-data

title: "Praktikum: Membuat Controller"
description: "Upload file controller yang telah Anda buat (.php) beserta screenshot hasil testing."
assignable_type: Lesson
assignable_slug: laravel-controllers
submission_type: file
max_score: 100
deadline_at: 2026-01-28 23:59:59
tolerance_minutes: 60
late_penalty_percent: 25
retake_enabled: false
status: published
attachments[0]: [file: panduan_praktikum.pdf]
```

**5. Mini Project Laravel Routing (File + Teks)**
*Lesson | Mixed | Bisa Resubmit | Hasil Ditunda*

Project kecil yang memerlukan upload file dan penjelasan tertulis.

```json
{
  "title": "Mini Project: Sistem Routing Multi-Level",
  "description": "Buat sistem routing dengan group, middleware, dan named routes. Upload file routes/web.php dan jelaskan struktur routing Anda.",
  "assignable_type": "Lesson",
  "assignable_slug": "laravel-routing",
  "submission_type": "mixed",
  "max_score": 150,
  "deadline_at": "2026-02-05 23:59:59",
  "tolerance_minutes": 0,
  "late_penalty_percent": 30,
  "max_attempts": 2,
  "retake_enabled": true,
  "review_mode": "deferred",
  "status": "published"
}
```

---

### 📗 Tipe: UNIT (Unit Pembelajaran)

**6. Evaluasi Tengah Unit (Bank Soal)**
*Unit | Mixed | 25 Soal dari 50 | 2 Percobaan*

Ujian tengah unit dengan sistem bank soal untuk variasi pertanyaan.

```json
{
  "title": "Evaluasi Tengah: Laravel Fundamentals",
  "description": "Evaluasi pemahaman Controllers dan Routing. 25 soal akan dipilih acak.",
  "assignable_type": "Unit",
  "assignable_slug": "mengenal-htmlcss1",
  "submission_type": "mixed",
  "max_score": 100,
  "available_from": "2026-02-01 09:00:00",
  "deadline_at": "2026-02-07 23:59:59",
  "tolerance_minutes": 20,
  "max_attempts": 2,
  "cooldown_minutes": 120,
  "review_mode": "deferred",
  "randomization_type": "bank",
  "question_bank_count": 25,
  "status": "published"
}
```

**7. Project Akhir Unit (File Upload)**
*Unit | File | No Retake | Penalti Berat*

Project besar akhir unit dengan penalti keterlambatan yang signifikan.

```http
POST /api/v1/assignments
Content-Type: multipart/form-data

title: "Project Akhir: Aplikasi CRUD Laravel"
description: "Buat aplikasi CRUD lengkap menggunakan Laravel. Upload dalam format .zip maksimal 50MB."
assignable_type: Unit
assignable_slug: mengenal-htmlcss1
submission_type: file
max_score: 300
deadline_at: 2026-02-15 23:59:59
tolerance_minutes: 30
late_penalty_percent: 40
max_attempts: 1
retake_enabled: false
review_mode: hidden
status: published
attachments[0]: [file: rubrik_penilaian.pdf]
attachments[1]: [file: template_project.zip]
```

**8. Kuis Komprehensif Unit (Urutan Acak)**
*Unit | Mixed | Unlimited Attempts | Hasil Langsung*

Kuis latihan dengan percobaan tidak terbatas untuk persiapan ujian.

```json
{
  "title": "Kuis Latihan: Persiapan Ujian Unit",
  "description": "Latihan soal untuk persiapan ujian akhir unit. Bisa dikerjakan berkali-kali.",
  "assignable_type": "Unit",
  "assignable_slug": "mengenal-htmlcss1",
  "submission_type": "mixed",
  "max_score": 100,
  "deadline_at": "2026-02-10 23:59:59",
  "max_attempts": null,
  "cooldown_minutes": 30,
  "review_mode": "immediate",
  "randomization_type": "random_order",
  "status": "published"
}
```

**9. Peer Review Unit (Teks)**
*Unit | Text | Partisipasi Wajib | Tidak Dinilai*

Review hasil kerja teman sejawat sebagai pembelajaran.

```json
{
  "title": "Peer Review: Evaluasi Project Teman",
  "description": "Berikan review konstruktif untuk project 2 teman Anda. Minimal 200 kata per review.",
  "assignable_type": "Unit",
  "assignable_slug": "mengenal-htmlcss1",
  "submission_type": "text",
  "max_score": 0,
  "deadline_at": "2026-02-18 23:59:59",
  "review_mode": "immediate",
  "status": "published"
}
```

---

### 📕 Tipe: COURSE (Kursus)

**10. Pre-Assessment Course (Bank Soal)**
*Course | Mixed | Diagnostik | 20 dari 40 Soal*

Tes awal untuk mengukur kemampuan dasar sebelum mulai kursus.

```json
{
  "title": "Pre-Assessment: Junior Web Programmer",
  "description": "Tes diagnostik untuk mengukur pengetahuan awal Anda. Hasil tidak mempengaruhi nilai akhir.",
  "assignable_type": "Course",
  "assignable_slug": "junior-web-programmer",
  "submission_type": "mixed",
  "max_score": 0,
  "available_from": "2026-01-20 00:00:00",
  "deadline_at": "2026-01-27 23:59:59",
  "max_attempts": 1,
  "review_mode": "immediate",
  "randomization_type": "bank",
  "question_bank_count": 20,
  "status": "published"
}
```

**11. Survei Feedback Course (Teks)**
*Course | Text | Anonim | Wajib Diisi*

Survei untuk mengumpulkan feedback dari siswa.

```json
{
  "title": "Survei Kepuasan: Junior Web Programmer",
  "description": "Berikan masukan Anda tentang kursus ini untuk peningkatan kualitas. Minimum 50 kata.",
  "assignable_type": "Course",
  "assignable_slug": "junior-web-programmer",
  "submission_type": "text",
  "max_score": 0,
  "deadline_at": "2026-06-30 23:59:59",
  "status": "published"
}
```

**12. Ujian Tengah Semester (High Stakes)**
*Course | Mixed | 1 Percobaan | 40 dari 80 Soal*

Ujian tengah semester dengan bobot tinggi dan soal dari bank soal.

```json
{
  "title": "Ujian Tengah Semester (UTS)",
  "description": "UTS Junior Web Programmer. 40 soal akan dipilih acak. Waktu pengerjaan 120 menit.",
  "assignable_type": "Course",
  "assignable_slug": "junior-web-programmer",
  "submission_type": "mixed",
  "max_score": 400,
  "available_from": "2026-03-15 09:00:00",
  "deadline_at": "2026-03-15 11:00:00",
  "tolerance_minutes": 0,
  "max_attempts": 1,
  "review_mode": "hidden",
  "randomization_type": "bank",
  "question_bank_count": 40,
  "status": "draft"
}
```

**13. Capstone Project Course (File + Dokumentasi)**
*Course | Mixed | Final Project | Manual Grading*

Project akhir kursus yang komprehensif dengan upload file dan dokumentasi.

```http
POST /api/v1/assignments
Content-Type: multipart/form-data

title: "Capstone Project: Web Application Portfolio"
description: "Buat aplikasi web lengkap sebagai portfolio akhir. Upload source code dan dokumentasi teknis."
assignable_type: Course
assignable_slug: junior-web-programmer
submission_type: mixed
max_score: 500
deadline_at: 2026-06-15 23:59:59
tolerance_minutes: 60
late_penalty_percent: 50
max_attempts: 2
retake_enabled: true
review_mode: hidden
status: published
attachments[0]: [file: panduan_capstone.pdf]
attachments[1]: [file: template_dokumentasi.docx]
attachments[2]: [file: checklist_penilaian.xlsx]
```

**14. Ujian Akhir Semester (UAS)**
*Course | Mixed | 50 Soal dari 100 | Proctored*

Ujian akhir semester dengan pengawasan ketat.

```json
{
  "title": "Ujian Akhir Semester (UAS)",
  "description": "UAS Junior Web Programmer. 50 soal komprehensif dari seluruh materi. Diawasi secara online.",
  "assignable_type": "Course",
  "assignable_slug": "junior-web-programmer",
  "submission_type": "mixed",
  "max_score": 500,
  "available_from": "2026-06-20 09:00:00",
  "deadline_at": "2026-06-20 11:30:00",
  "tolerance_minutes": 0,
  "max_attempts": 1,
  "review_mode": "deferred",
  "randomization_type": "bank",
  "question_bank_count": 50,
  "status": "draft"
}
```

**15. Post-Assessment Course (Evaluasi Akhir)**
*Course | Mixed | 30 Soal Acak | Mengukur Progress*

Tes akhir untuk membandingkan dengan pre-assessment.

```json
{
  "title": "Post-Assessment: Evaluasi Kemajuan Belajar",
  "description": "Tes untuk mengukur peningkatan kemampuan Anda setelah menyelesaikan kursus.",
  "assignable_type": "Course",
  "assignable_slug": "junior-web-programmer",
  "submission_type": "mixed",
  "max_score": 100,
  "deadline_at": "2026-06-25 23:59:59",
  "max_attempts": 1,
  "review_mode": "immediate",
  "randomization_type": "bank",
  "question_bank_count": 30,
  "status": "published"
}
```

---

### 4. Dapatkan Detail Tugas

**Endpoint:** `GET /assignments/{id}`

Mengambil detail lengkap dari satu tugas.

**Contoh Respons:**
```json
{
  "data": {
    "id": 15,
    "title": "Kuis Modul 1",
    "assignable_type": "Lesson",
    "assignable_slug": "php-basics",
    "lesson_slug": "php-basics",
    "unit_slug": "intro-unit",
    "course_slug": "web-bootcamp",
    "settings": {
        "max_score": 100,
        "max_attempts": 3,
        "review_mode": "deferred"
    },
    "media": [
        { "id": 1, "url": "https://cdn.../rules.pdf" }
    ],
    "questions": [
        { "id": 101, "content": "Apa itu PHP?", "type": "multiple_choice" }
    ]
  }
}
```

---

### 5. Perbarui Tugas

**Endpoint:** `PUT /assignments/{id}`

Perbarui field apa pun yang diizinkan dalam Create. Kirim `null` untuk mengosongkan field yang nullable.

**Contoh Body:**
```json
{
    "title": "Judul Kuis yang Diperbarui",
    "deadline_at": "2024-03-10 23:59:59",
    "tolerance_minutes": 60,
    "max_attempts": 5,
    "retake_enabled": true,
    "review_mode": "immediate",
    "late_penalty_percent": 5,
    "status": "published",
    "delete_attachments": [12, 15]
}
```

---

### 6. Duplikasi Tugas

**Endpoint:** `POST /assignments/{id}/duplicate`

Kloning tugas. Timpa field di body jika diperlukan.

**Contoh Body:**
```json
{ 
  "title": "Salinan Kuis 1", 
  "available_from": "2024-05-01 08:00:00" 
}
```

---

### 7. Publikasi / Batalkan Publikasi

**Publikasi:**
**Endpoint:** `PUT /assignments/{id}/publish`

> [!IMPORTANT]
> Publikasi akan ditolak jika total bobot seluruh soal melebihi `max_score` assignment. Saat draf, Anda bisa menambahkan soal melebihi total, namun saat publish sistem akan memvalidasi dan mengembalikan pesan kesalahan.

**Batalkan Publikasi:**
**Endpoint:** `PUT /assignments/{id}/unpublish`

---

### 8. Arsipkan Tugas

**Endpoint:** `PUT /assignments/{id}/archived`

Mengubah status tugas menjadi "archived".

**Respons:** `200 OK`

---

### 9. Hapus Tugas

**Endpoint:** `DELETE /assignments/{id}`

Hapus permanen tugas dan semua soal yang terkait.

**Respons:** `200 OK`

---

## ❓ Manajemen Soal (Questions)

### 1. Daftar Soal

### 1. Daftar Soal

**Endpoint:** `GET /assignments/{id}/questions`

Mengambil daftar semua soal dalam tugas. 

> [!WARNING]
> **RESTRICTED ACCESS:** Endpoint ini HANYA untuk **Admin dan Instructor**. Siswa TIDAK BISA mengakses endpoint ini. Siswa harus menggunakan `GET /submissions/{id}/questions` untuk melihat soal ujian mereka.

**Parameter Query (Admin/Instructor Only):**

| Parameter | Tipe | Deskripsi |
|-----------|------|-------------|
| `search` | string | Cari konten soal (Meilisearch) |
| `filter[type]` | string | Filter tipe soal |
| `sort` | string | Urutkan hasil |

**Contoh Request:**
`GET /assignments/1/questions?search=Laravel&sort=-weight`

---

### 2. Tambah Soal

**Endpoint:** `POST /assignments/{id}/questions`

**Body (JSON):**

| Field | Tipe | Wajib | Deskripsi | Nilai yang Tersedia |
|-------|------|----------|-------------|---------------------|
| `type` | enum | Ya | Tipe soal | `multiple_choice` (Pilihan ganda - pilih satu)<br>`checkbox` (Pilihan ganda - pilih banyak)<br>`essay` (Esai/teks bebas)<br>`file_upload` (Upload file sebagai jawaban) |
| `content` | string | Ya | Teks/konten soal | - |
| `options` | array | **Wajib (Pilihan)** | Daftar opsi jawaban. Bisa string atau object `{text, image}`. | - |
| `answer_key` | array | **Wajib (Otomatis)** | Kunci jawaban (index opsi) | - |
| `weight` | float | **Ya** | Bobot poin soal | Default: 1 |

> [!NOTE]
> Selama status tugas masih draf, Anda boleh menambahkan soal meskipun total `weight` melebihi `max_score`. Namun, saat publish sistem akan menolak jika total bobot melebihi `max_score`.
| `max_score` | float | Tidak | Skor maksimum (biasanya sama dengan weight) | - |
| `allowed_file_types` | array | **Wajib (Upload)** | Ekstensi yang diizinkan | `['jpg', 'pdf', ...]` |
| `max_file_size` | int | Tidak | Maksimum ukuran file (bytes) | Default: system setting |
| `allow_multiple_files` | boolean | Tidak | Izinkan upload banyak file? | Default: false |

**Contoh Body per Tipe Soal:**

**1. Pilihan Ganda (Teks Biasa)**
```json
{
  "type": "multiple_choice",
  "content": "Apa kepanjangan dari PHP?",
  "options": [
    "Personal Home Page",
    "PHP: Hypertext Preprocessor"
  ],
  "answer_key": [1],
  "weight": 5
}
```

**2. Pilihan Ganda / Checkbox (Dengan Gambar)**
*Opsi bisa berupa Object yang berisi `text` dan `image`.*
```json
{
  "type": "checkbox",
  "content": "Pilih hewan yang berkaki empat:",
  "options": [
    { "text": "Kucing", "image": "https://example.com/cat.jpg" },
    { "text": "Ayam", "image": "https://example.com/chicken.jpg" },
    { "text": "Sapi", "image": null }
  ],
  "answer_key": [0, 2],
  "weight": 10
}
```

**3. Esai (Essay)**
```json
{
  "type": "essay",
  "content": "Jelaskan sejarah PHP secara singkat.",
  "options": [],
  "answer_key": [],
  "weight": 10
}
```

**4. Upload File**
```json
{
  "type": "file_upload",
  "content": "Upload diagram database anda.",
  "allowed_file_types": ["pdf", "jpg", "png"],
  "max_file_size": 5242880, // 5MB in bytes
  "allow_multiple_files": false,
  "weight": 20
}
```

---

### 3. Perbarui Soal

**Endpoint:** `PUT /assignments/{id}/questions/{question_id}`

Update properti soal. Kirim field yang ingin diubah saja.

**Body (JSON):**
```json
{
  "content": "Pertanyaan yang diperbarui?",
  "points": 10
}
```

---

### 4. Hapus Soal

**Endpoint:** `DELETE /assignments/{id}/questions/{question_id}`

Menghapus soal dari tugas.
**Respons:** `200 OK`

---

### 5. Urutkan Ulang Soal

**Endpoint:** `POST /assignments/{id}/questions/reorder`

Ubah urutan tampilan soal.

**Body:**
```json
{
  "ids": [102, 101, 103]
}
```

---

## ⚡ Override Akses (Admin/Instruktur)

### 1. Daftar Override

**Endpoint:** `GET /assignments/{id}/overrides`

Melihat daftar override yang diberikan kepada siswa untuk tugas ini.

### 2. Berikan Override

**Endpoint:** `POST /assignments/{id}/overrides`

Memberikan akses khusus (tambahan waktu, percobaan, atau bypass prasyarat).

**Body (JSON):**

| Field | Tipe | Wajib | Deskripsi |
|-------|------|-------|-------------|
| `student_id` | int | Ya | ID Siswa |
| `type` | enum | Ya | `attempts`, `deadline`, `prerequisite` |
| `reason` | string | Ya | Alasan pemberian override |
| `value` | object | Ya | Nilai override (lihat contoh) |

**Contoh 1: Tambah Percobaan**
```json
{
  "student_id": 105,
  "type": "attempts",
  "reason": "Koneksi internet terputus saat pengerjaan.",
  "value": {
    "additional_attempts": 1
  }
}
```

**Contoh 2: Perpanjang Deadline**
```json
{
  "student_id": 105,
  "type": "deadline",
  "reason": "Sakit (ada surat dokter).",
  "value": {
    "extended_deadline": "2026-04-01 23:59:59"
  }
}
```

---

## 📤 Pengumpulan (Submissions - Untuk Siswa)

### 1. Dapatkan Pengumpulan Saya

**Endpoint:** `GET /assignments/{id}/submissions/me`

Melihat semua percobaan pengumpulan milik siswa untuk tugas tertentu.

---

### 2. Periksa Percobaan / Tenggat Waktu

**Periksa Percobaan:**
**Endpoint:** `GET /assignments/{id}/attempts/check`

Cek apakah siswa masih bisa mencoba lagi.

**Periksa Tenggat Waktu:**
**Endpoint:** `GET /assignments/{id}/deadline/check`

Cek apakah tenggat waktu sudah lewat atau masih ada toleransi.

---

### 3. Mulai Percobaan

**Endpoint:** `POST /assignments/{id}/submissions/start`

Memulai sesi pengerjaan baru. Mengembalikan `submission_id` yang digunakan untuk submit.

Jika `time_limit_minutes` diatur pada assignment, UI sebaiknya menampilkan hitung mundur. Server juga menegakkan batas waktu dengan toleransi jaringan 60 detik (grace buffer).

---

### 4. Ambil Soal Ujian (Active Question Set)

**Endpoint:** `GET /submissions/{submission_id}/questions`

Mengambil daftar soal yang **sudah diurutkan (randomized)** khusus untuk sesi ujian siswa ini.
Respons juga menyertakan `current_answer` jika siswa sudah pernah menyimpan jawaban sebelumnya (Resume Progress).

**Respons:**
```json
{
  "data": [
    {
      "id": 105,
      "content": "Pertanyaan No 1...",
      "current_answer": {
        "content": "Jawaban Saya"
      }
    },
    {
      "id": 203,
      "content": "Pertanyaan No 2...",
      "current_answer": null
    }
  ]
}
```

---

### 5. Simpan Jawaban (Progres)

**Endpoint:** `POST /submissions/{submission_id}/answers`

Menyimpan atau memperbarui jawaban untuk satu soalan tertentu. Endpoint ini dapat dipanggil berkali-kali sebelum melakukan final submit.

Jika timer kedaluwarsa, server akan menolak penyimpanan dengan pesan kesalahan `timer_expired`.

**Contoh Body per Tipe Soal:**

**1. Esai (Essay)**
```json
{
  "question_id": 101,
  "answer": "Jawaban teks esai saya."
}
```

**2. Pilihan Ganda (Multiple Choice - Single)**
*Kirim value opsi yang dipilih (bukan index array).*
```json
{
  "question_id": 102,
  "answer": "Jakarta"
}
```

**3. Pilihan Ganda Majemuk (Checkbox - Multiple)**
*Kirim array value opsi yang dipilih.*
```json
{
  "question_id": 103,
  "answer": ["Jakarta", "Bandung"]
}
```

**4. Upload File**
*Wajib menggunakan `multipart/form-data`, bukan JSON raw.*
```http
POST /api/v1/submissions/{submission_id}/answers
Content-Type: multipart/form-data

question_id: 104
answer: [File Binary / Gambar]
```

---

### 6. Kirim Percobaan

**Endpoint:** `POST /submissions/{submission_id}/submit`

Mengirimkan jawaban siswa untuk disubmit (final).

Jika timer kedaluwarsa, server akan menolak submit dengan pesan kesalahan `timer_expired`. Setelah lewat `deadline_at + tolerance_minutes`, percobaan yang masih `in_progress` akan otomatis ditandai sebagai `missing` oleh proses housekeeping setiap menit.

**Body (tergantung `submission_type`):**

**Untuk `text`:**
```json
{
  "answers": [
    { "question_id": 101, "answer": "PHP adalah bahasa pemrograman..." }
  ]
}
```

**Untuk `file`:**
```http
POST /api/v1/submissions/{submission_id}/submit
Content-Type: multipart/form-data

file: [upload file]
```

**Untuk `mixed`:**
```http
POST /api/v1/submissions/{submission_id}/submit
Content-Type: multipart/form-data

answers[0][question_id]: 101
answers[0][answer]: "Teks jawaban"
answers[1][question_id]: 102
answers[1][file]: [upload file]
```

---

### 5. Detail Pengumpulan

**Endpoint:** `GET /submissions/{id}`

Melihat detail satu pengumpulan spesifik (jawaban, status, nilai).

### 6. Update Pengumpulan (Simpan Draf)

**Endpoint:** `PUT /submissions/{id}`

Menyimpan jawaban sementara tanpa mengubah status menjadi submitted (berguna untuk auto-save). Format body sama dengan endpoint Submit.

---

## 🎓 Penilaian (Grading - Untuk Instruktur)

### 1. Daftar Pengumpulan
**Endpoint:** `GET /assignments/{id}/submissions`

Melihat semua pengumpulan siswa untuk tugas tertentu.

**Parameter Query:**

| Parameter | Tipe | Deskripsi | Nilai yang Tersedia |
|-----------|------|-------------|---------------------|
| `filter[status]` | string | Filter berdasarkan status | `draft`, `submitted`, `graded`, `late` |
| `filter[status]` | string | Filter berdasarkan status | Tambahan: `missing` |
| `filter[user_id]` | int | Filter berdasarkan siswa | ID Siswa |
| `filter[is_late]` | bool | Filter keterlambatan | `true` (Terlambat), `false` (Tepat waktu) |
| `filter[score_range]` | string | Filter rentang nilai | Format: `min,max` (Contoh: `0,50`) |
| `sort` | string | Urutkan hasil | `submitted_at`, `-submitted_at` (Default), `created_at`, `-created_at`, `score`, `-score`, `status` |
| `page` | int | Nomor halaman | Default: 1 |
| `per_page` | int | Item per halaman | Default: 15 |

**Contoh Request:**
`GET /assignments/1/submissions?filter[status]=graded&sort=-score&page=1`

**Contoh Respons:**
```json
{
  "success": true,
  "message": "Data berhasil diambil.",
  "data": [
    {
      "id": 105,
      "user": { "id": 5, "name": "Budi Santoso" },
      "status": "graded",
      "score": 85,
      "submitted_at": "2024-03-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

---

### 2. Nilai Pengumpulan

**Endpoint:** `POST /submissions/{submission_id}/grade`

Memberikan nilai dan feedback pada pengumpulan siswa.

**Body:**
```json
{
  "score": 85,
  "feedback": "Kerja yang bagus. Perhatikan penjelasan di nomor 3.",
  "status": "graded"
}
```

**Field:**

| Field | Tipe | Wajib | Deskripsi | Nilai yang Tersedia |
|-------|------|----------|-------------|---------------------|
| `score` | int | Ya | Skor yang diberikan | 0 - `max_score` assignment |
| `feedback` | string | Tidak | Catatan/komentar instruktur | - |
| `status` | enum | Ya | Status setelah dinilai | `graded` (Dinilai)<br>`needs_revision` (Perlu revisi) |

---

## 🛠️ Helper Endpoints

### 1. Cek Prasyarat

**Endpoint:** `GET /assignments/{id}/prerequisites/check`

Mengecek apakah siswa memenuhi syarat untuk mengerjakan tugas ini.
**Respons:** `200 OK` (jika memenuhi) atau `422 Unprocessable Entity` (jika terkunci).

### 2. Pengumpulan Tertinggi

**Endpoint:** `GET /assignments/{id}/submissions/highest`

Mengambil data pengumpulan dengan nilai tertinggi **milik siswa yang sedang login**.

---

## ⚠️ Kode Error

| Kode | Arti | Penjelasan |
|------|---------|------------|
| 422 | Validation Error | Data yang dikirim tidak valid atau tidak lengkap |
| 403 | Forbidden | Tidak memiliki izin untuk aksi ini |
| 404 | Not Found | Resource tidak ditemukan |
| 401 | Unauthorized | Belum login atau token tidak valid |