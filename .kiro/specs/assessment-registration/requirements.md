# Requirements Document

## Introduction

Sistem Assessment Registration adalah fitur yang memungkinkan asesi untuk mendaftar assessment secara terpisah dari enrollment course. Sistem ini mendukung scheduling, prerequisites checking, payment processing, dan status tracking untuk proses assessment yang terstruktur.

## Glossary

- **Assessment Registration System**: Sistem yang mengelola pendaftaran assessment
- **Assessment**: Ujian atau evaluasi kompetensi yang terpisah dari course
- **Registration**: Proses pendaftaran untuk mengikuti assessment
- **Assessment Schedule**: Jadwal pelaksanaan assessment
- **Prerequisites**: Syarat yang harus dipenuhi sebelum dapat mendaftar assessment
- **Assessment Slot**: Slot waktu yang tersedia untuk assessment
- **Registration Status**: Status pendaftaran (pending, approved, rejected, completed)

## Requirements

### Requirement 1

**User Story:** Sebagai asesi, saya ingin mendaftar assessment, sehingga saya dapat mengikuti ujian kompetensi.

#### Acceptance Criteria

1. WHEN asesi mendaftar assessment THEN the Assessment Registration System SHALL create registration record dengan user ID dan assessment ID
2. WHEN pendaftaran dibuat THEN the Assessment Registration System SHALL menyimpan timestamp pendaftaran dan status pending
3. WHEN asesi mendaftar THEN the Assessment Registration System SHALL memvalidasi bahwa assessment tersedia untuk pendaftaran
4. WHEN pendaftaran dibuat THEN the Assessment Registration System SHALL generate unique registration number
5. WHEN asesi mendaftar THEN the Assessment Registration System SHALL mengirim email konfirmasi pendaftaran

### Requirement 2

**User Story:** Sebagai asesi, saya ingin memilih jadwal assessment, sehingga saya dapat mengikuti assessment pada waktu yang sesuai.

#### Acceptance Criteria

1. WHEN asesi memilih jadwal THEN the Assessment Registration System SHALL menampilkan available assessment slots
2. WHEN slot dipilih THEN the Assessment Registration System SHALL memvalidasi bahwa slot masih tersedia
3. WHEN jadwal dipilih THEN the Assessment Registration System SHALL reserve slot untuk user dengan timeout
4. WHEN pendaftaran dikonfirmasi THEN the Assessment Registration System SHALL confirm slot reservation
5. WHEN slot penuh THEN the Assessment Registration System SHALL menampilkan error dan suggest alternative slots

### Requirement 3

**User Story:** Sebagai asesi, saya ingin melihat prerequisites assessment, sehingga saya dapat mempersiapkan diri sebelum mendaftar.

#### Acceptance Criteria

1. WHEN asesi melihat assessment details THEN the Assessment Registration System SHALL menampilkan daftar prerequisites
2. WHEN prerequisites ditampilkan THEN the Assessment Registration System SHALL menampilkan status completion untuk setiap prerequisite
3. WHEN asesi mendaftar THEN the Assessment Registration System SHALL memvalidasi bahwa semua prerequisites terpenuhi
4. WHEN prerequisites tidak terpenuhi THEN the Assessment Registration System SHALL menolak pendaftaran dengan pesan error
5. WHEN prerequisites terpenuhi THEN the Assessment Registration System SHALL memungkinkan pendaftaran

### Requirement 4

**User Story:** Sebagai asesi, saya ingin melihat status pendaftaran assessment saya, sehingga saya dapat tracking proses pendaftaran.

#### Acceptance Criteria

1. WHEN asesi mengakses registration list THEN the Assessment Registration System SHALL menampilkan semua registrations dengan status
2. WHEN status ditampilkan THEN the Assessment Registration System SHALL menampilkan: pending, approved, rejected, completed, cancelled
3. WHEN registration ditampilkan THEN the Assessment Registration System SHALL menampilkan assessment name, schedule, dan registration number
4. WHEN status berubah THEN the Assessment Registration System SHALL mengirim notifikasi kepada asesi
5. WHEN asesi mengakses detail THEN the Assessment Registration System SHALL menampilkan timeline status changes

### Requirement 5

**User Story:** Sebagai asesi, saya ingin membatalkan pendaftaran assessment, sehingga saya dapat membatalkan jika berhalangan.

#### Acceptance Criteria

1. WHEN asesi membatalkan registration THEN the Assessment Registration System SHALL update status menjadi cancelled
2. WHEN pembatalan dilakukan THEN the Assessment Registration System SHALL memvalidasi cancellation policy (minimal X hari sebelum assessment)
3. WHEN registration dibatalkan THEN the Assessment Registration System SHALL release assessment slot
4. WHEN pembatalan terjadi THEN the Assessment Registration System SHALL mengirim email konfirmasi pembatalan
5. WHEN pembatalan terlambat THEN the Assessment Registration System SHALL menolak pembatalan dan tampilkan pesan error

### Requirement 6

**User Story:** Sebagai admin, saya ingin menyetujui atau menolak pendaftaran assessment, sehingga saya dapat mengontrol siapa yang dapat mengikuti assessment.

#### Acceptance Criteria

1. WHEN admin mereview registration THEN the Assessment Registration System SHALL menampilkan registration details dan user profile
2. WHEN admin menyetujui THEN the Assessment Registration System SHALL update status menjadi approved dan kirim notifikasi
3. WHEN admin menolak THEN the Assessment Registration System SHALL update status menjadi rejected dengan rejection reason
4. WHEN status diubah THEN the Assessment Registration System SHALL mencatat admin yang melakukan approval/rejection
5. WHEN rejection terjadi THEN the Assessment Registration System SHALL release assessment slot

### Requirement 7

**User Story:** Sebagai admin, saya ingin mengelola jadwal assessment, sehingga saya dapat mengatur kapasitas dan waktu pelaksanaan.

#### Acceptance Criteria

1. WHEN admin membuat schedule THEN the Assessment Registration System SHALL menyimpan date, time, location, dan capacity
2. WHEN schedule dibuat THEN the Assessment Registration System SHALL memvalidasi bahwa date di masa depan
3. WHEN admin mengedit schedule THEN the Assessment Registration System SHALL update schedule dan notifikasi registered users
4. WHEN admin menghapus schedule THEN the Assessment Registration System SHALL memvalidasi tidak ada approved registrations
5. WHEN capacity penuh THEN the Assessment Registration System SHALL close registration untuk schedule tersebut

### Requirement 8

**User Story:** Sebagai asesi, saya ingin melihat hasil assessment saya, sehingga saya dapat mengetahui performance saya.

#### Acceptance Criteria

1. WHEN assessment completed THEN the Assessment Registration System SHALL update registration status menjadi completed
2. WHEN hasil tersedia THEN the Assessment Registration System SHALL menampilkan score, grade, dan feedback
3. WHEN asesi mengakses hasil THEN the Assessment Registration System SHALL menampilkan detailed breakdown per competency
4. WHEN hasil ditampilkan THEN the Assessment Registration System SHALL menampilkan certificate download jika passed
5. WHEN hasil tersedia THEN the Assessment Registration System SHALL mengirim notifikasi kepada asesi

### Requirement 9

**User Story:** Sebagai instruktur, saya ingin melihat daftar peserta assessment, sehingga saya dapat mempersiapkan pelaksanaan assessment.

#### Acceptance Criteria

1. WHEN instruktur mengakses participant list THEN the Assessment Registration System SHALL menampilkan approved registrations
2. WHEN list ditampilkan THEN the Assessment Registration System SHALL menampilkan nama, registration number, dan contact info
3. WHEN instruktur mengakses THEN the Assessment Registration System SHALL mendukung filter berdasarkan schedule dan status
4. WHEN list diakses THEN the Assessment Registration System SHALL mendukung export ke PDF atau Excel
5. WHEN instruktur mengakses THEN the Assessment Registration System SHALL menampilkan attendance checklist

### Requirement 10

**User Story:** Sebagai sistem, saya ingin mengirim reminder kepada peserta assessment, sehingga peserta tidak lupa jadwal assessment.

#### Acceptance Criteria

1. WHEN assessment H-7 THEN the Assessment Registration System SHALL mengirim reminder email kepada approved participants
2. WHEN assessment H-1 THEN the Assessment Registration System SHALL mengirim reminder email dan push notification
3. WHEN reminder dikirim THEN the Assessment Registration System SHALL include assessment details, location, dan requirements
4. WHEN assessment hari H THEN the Assessment Registration System SHALL mengirim reminder 2 jam sebelum assessment
5. WHEN reminder dikirim THEN the Assessment Registration System SHALL mencatat log pengiriman reminder
