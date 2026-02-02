# Dokumentasi API Gamification

Dokumentasi lengkap untuk endpoint yang tersedia di modul Gamification.

Base URL: `/api/v1`

## Autentikasi
Semua endpoint dalam modul ini membutuhkan autentikasi menggunakan **Bearer Token**.
- **Header:** `Authorization: Bearer <token>`
- **Akses User:** Semua role user yang terautentikasi (`Student`, `Instructor`, `Admin`, `Superadmin`) dapat mengakses endpoint ini. Setiap user akan melihat data gamifikasi (XP, Badge, Level) milik mereka sendiri atau data publik (Leaderboard).

## Daftar Endpoint

### 1. Challenges (Tantangan)

#### A. List Semua Tantangan
Mendapatkan daftar semua tantangan yang aktif.

- **Endpoint:** `GET /challenges`
- **Auth:** Required
- **Parameter Query (Optional):**
  - `page` (int): Halaman yang ingin ditampilkan (Default: 1).
  - `per_page` (int): Jumlah item per halaman (Default: 15).
  - `filter[type]` (string): Filter berdasarkan tipe (`daily`, `weekly`, `special`).
  - `filter[status]` (string): Filter status (`active`, `expired`).
  - `filter[points_reward]` (int): Filter jumlah poin.
  - `filter[criteria_type]` (string): Filter tipe kriteria. Value: `lessons_completed`, `exercises_completed`, `assignments_submitted`, `xp_earned`.
  - `filter[has_progress]` (boolean): Filter tantangan yang sudah ada progress user (`true` / `false`). (Butuh Login)
  - `sort` (string): Sorting data. Field yang didukung: `points_reward`, `created_at`, `type`.
    - Prefix `-` untuk descending (Contoh: `sort=-points_reward`).
- **Response:**
  Mengembalikan daftar tantangan beserta progress user saat ini (jika ada).
  - Field penting: `criteria_type` (tipe aksi), `criteria_target` (jumlah target).
  - `start_at` & `end_at` untuk melihat durasi tantangan.
  - Default sort: `-points_reward`.

#### B. Detail Tantangan
Mendapatkan detail dari satu tantangan spesifik.

- **Endpoint:** `GET /challenges/{id}`
- **Auth:** Required
- **Parameter Path:**
  - `id` (int): ID dari tantangan.
- **Response:**
  Object detail tantangan, termasuk status progress user (progress, target, percentage, status, dll).
  - `criteria_type`: Tipe kriteria (`lessons_completed`, `assignments_submitted`, `exercises_completed`, `xp_earned`).
  - `start_at`: Tanggal mulai tantangan (datetime string).
  - `end_at`: Tanggal berakhir tantangan (datetime string).

#### C. Klaim Reward Tantangan
Mengklaim hadiah (poin/badge) setelah tantangan diselesaikan.

- **Endpoint:** `POST /challenges/{id}/claim`
- **Auth:** Required
- **Parameter Path:**
  - `id` (int): ID dari tantangan.
- **Body:** Tidak ada (Empty body).
- **Response:**
  - `message`: "Reward berhasil diklaim"
  - `rewards`: Detail hadiah yang didapatkan.

#### D. Tantangan Saya (Aktif)
Mendapatkan daftar tantangan yang sedang diikuti (assigned) oleh user.

- **Endpoint:** `GET /user/challenges`
- **Auth:** Required
- **Parameter:** Tidak ada.
- **Response:**
  List `UserChallengeAssignmentResource`.

#### E. Tantangan Selesai
Mendapatkan daftar tantangan yang sudah diselesaikan oleh user.

- **Endpoint:** `GET /user/challenges/completed`
- **Auth:** Required
- **Parameter Query (Optional):**
  - `limit` (int): Batas jumlah data yang diambil (Default: 15).
- **Response:**
  List `ChallengeCompletionResource`.

---

### 2. Leaderboard (Peringkat)

#### A. Leaderboard Global & Course
Mendapatkan daftar peringkat user. Jika parameter `course_id` dikirim, akan menampilkan leaderboard spesifik untuk kursus tersebut. Jika tidak, akan menampilkan leaderboard global.

- **Endpoint:** `GET /leaderboards`
- **Auth:** Required
- **Parameter Query (Optional):**
  - `page` (int): Halaman (Default: 1).
  - `per_page` (int): Jumlah per halaman (Default: 10, Max: 100).
  - `course_slug` (string): Slug Kursus untuk filter leaderboard spesifik kursus.
    - *Contoh:* `?course_slug=belajar-laravel-dasar`
- **Response:**
  Paginated list dari user beserta rank (peringkat) dan total XP mereka.
  - Sorting otomatis berdasarkan Peringkat / Total XP.

#### B. Peringkat Saya
Mendapatkan informasi peringkat user yang sedang login saat ini, termasuk user di sekitar peringkatnya (atas dan bawah).

- **Endpoint:** `GET /user/rank`
- **Auth:** Required
- **Parameter:** Tidak ada.
- **Response:**
  - `rank`: Peringkat saat ini.
  - `total_xp`: Total XP user.
  - `level`: Level global user.
  - `surrounding`: Array user lain yang berada di peringkat atas dan bawah user ini.

---

### 3. User Gamification Data

#### A. Summary Gamification
Ringkasan statistik gamifikasi user.

- **Endpoint:** `GET /user/gamification-summary`
- **Auth:** Required
- **Parameter:** Tidak ada.
- **Response:**
  Data summary seperti level, xp, streak, dll.

#### B. Badge Saya
Daftar semua badge yang telah didapatkan oleh user.

- **Endpoint:** `GET /user/badges`
- **Auth:** Required
- **Parameter:** Tidak ada.
- **Response:**
  List `UserBadgeResource` (berisi detail badge dan tanggal didapatkan).

#### C. Riwayat Poin
Mendapatkan riwayat perolehan poin user.

- **Endpoint:** `GET /user/points-history`
- **Auth:** Required
- **Parameter Query (Optional):**
  - `per_page` (int): Jumlah item per halaman (Default: 15).
  - `page` (int): Halaman.
  - `filter[source_type]` (string): Filter sumber poin (`assignment`, `course`, `login`, dll).
  - `filter[reason]` (string): Filter alasan perolehan.
  - `sort` (string): Field sorting: `created_at`, `points`, `source_type`. (Default: `-created_at`).
- **Response:**
  Paginated list transaksi poin (sumber, jumlah, alasan).

#### D. Achievements
Mendapatkan daftar pencapaian (achievements).

- **Endpoint:** `GET /user/achievements`
- **Auth:** Required
- **Parameter:** Tidak ada.
- **Response:**
  Data achievements.

#### E. Level Unit Kursus
Mendapatkan level user untuk unit-unit di dalam kursus tertentu.

- **Endpoint:** `GET /user/levels/{slug}`
- **Auth:** Required
- **Parameter Path:**
  - `slug` (string): Slug Kursus.
- **Response:**
  - `unit_levels`: Data level per unit.

---

## Catatan
- Semua request method **POST** yang tidak membutuhkan body data tetap harus mengirimkan header `Content-Type: application/json` dan `Accept: application/json`.
- Pagination menggunakan standar Laravel Paginator (links, meta info).
