# Requirements Document

## Introduction

Sistem Forum/Diskusi adalah fitur yang memungkinkan asesi, instruktur, dan admin untuk berkomunikasi dan berdiskusi dalam konteks skema tertentu. Sistem ini mendukung thread diskusi, balasan, moderasi, dan notifikasi untuk meningkatkan kolaborasi dan pembelajaran dalam platform.

## Glossary

- **Forum System**: Sistem yang mengelola diskusi berbasis thread dalam konteks skema pembelajaran
- **Thread**: Topik diskusi utama yang dibuat oleh pengguna
- **Reply**: Balasan atau komentar terhadap thread atau reply lainnya
- **Asesi**: Peserta yang terdaftar dalam skema pembelajaran
- **Instruktur**: Pengajar yang bertanggung jawab atas skema pembelajaran
- **Moderator**: Pengguna dengan hak khusus untuk mengelola konten forum (admin atau instruktur)
- **Scheme Context**: Konteks skema dimana forum berada, membatasi akses berdasarkan enrollment

## Requirements

### Requirement 1

**User Story:** Sebagai asesi, saya ingin membuat thread diskusi baru dalam skema yang saya ikuti, sehingga saya dapat bertanya atau berbagi informasi dengan peserta lain.

#### Acceptance Criteria

1. WHEN asesi yang terdaftar dalam skema membuat thread THEN the Forum System SHALL create thread dengan judul, konten, dan metadata penulis
2. WHEN asesi membuat thread THEN the Forum System SHALL validate bahwa asesi terdaftar dalam skema tersebut
3. WHEN thread dibuat THEN the Forum System SHALL menyimpan timestamp pembuatan dan status thread
4. WHEN asesi mencoba membuat thread di skema yang tidak diikuti THEN the Forum System SHALL menolak permintaan dan mengembalikan error authorization
5. WHEN thread dibuat THEN the Forum System SHALL mengirim notifikasi kepada instruktur skema

### Requirement 2

**User Story:** Sebagai pengguna, saya ingin membalas thread atau reply yang ada, sehingga saya dapat berpartisipasi dalam diskusi.

#### Acceptance Criteria

1. WHEN pengguna yang terdaftar dalam skema membalas thread THEN the Forum System SHALL create reply dengan konten dan referensi ke parent
2. WHEN reply dibuat THEN the Forum System SHALL mendukung nested replies hingga level tertentu
3. WHEN reply dibuat THEN the Forum System SHALL menyimpan timestamp dan metadata penulis
4. WHEN pengguna membalas THEN the Forum System SHALL mengirim notifikasi kepada penulis thread atau reply yang dibalas
5. WHEN pengguna mencoba membalas thread di skema yang tidak diikuti THEN the Forum System SHALL menolak permintaan

### Requirement 3

**User Story:** Sebagai pengguna, saya ingin melihat daftar thread dalam skema, sehingga saya dapat menemukan diskusi yang relevan.

#### Acceptance Criteria

1. WHEN pengguna mengakses forum skema THEN the Forum System SHALL menampilkan daftar thread yang diurutkan berdasarkan aktivitas terbaru
2. WHEN menampilkan thread THEN the Forum System SHALL menampilkan judul, penulis, jumlah reply, dan timestamp terakhir
3. WHEN pengguna mencari thread THEN the Forum System SHALL mendukung pencarian berdasarkan judul dan konten
4. WHEN menampilkan daftar THEN the Forum System SHALL mendukung pagination dengan maksimal 20 thread per halaman
5. WHEN pengguna mengakses forum THEN the Forum System SHALL memfilter thread berdasarkan skema yang diikuti pengguna

### Requirement 4

**User Story:** Sebagai pengguna, saya ingin melihat detail thread beserta semua replies, sehingga saya dapat mengikuti diskusi lengkap.

#### Acceptance Criteria

1. WHEN pengguna membuka thread THEN the Forum System SHALL menampilkan konten lengkap thread dan semua replies
2. WHEN menampilkan replies THEN the Forum System SHALL menampilkan replies dalam struktur hierarki yang jelas
3. WHEN thread ditampilkan THEN the Forum System SHALL menampilkan informasi penulis, timestamp, dan status edit
4. WHEN pengguna membuka thread THEN the Forum System SHALL increment view counter thread
5. WHEN menampilkan thread THEN the Forum System SHALL menampilkan replies yang diurutkan berdasarkan timestamp

### Requirement 5

**User Story:** Sebagai penulis thread atau reply, saya ingin mengedit atau menghapus konten saya, sehingga saya dapat memperbaiki kesalahan atau menghapus konten yang tidak relevan.

#### Acceptance Criteria

1. WHEN penulis mengedit thread atau reply THEN the Forum System SHALL update konten dan menyimpan timestamp edit
2. WHEN konten diedit THEN the Forum System SHALL menandai konten sebagai edited dengan timestamp
3. WHEN penulis menghapus thread atau reply THEN the Forum System SHALL melakukan soft delete dan menyimpan metadata penghapusan
4. WHEN pengguna bukan penulis mencoba mengedit THEN the Forum System SHALL menolak permintaan
5. WHEN thread dihapus THEN the Forum System SHALL menyembunyikan thread dari daftar tetapi tetap menyimpan data

### Requirement 6

**User Story:** Sebagai moderator, saya ingin mengelola konten forum, sehingga saya dapat menjaga kualitas diskusi dan menghapus konten yang tidak pantas.

#### Acceptance Criteria

1. WHEN moderator menandai thread sebagai pinned THEN the Forum System SHALL menampilkan thread di bagian atas daftar
2. WHEN moderator menutup thread THEN the Forum System SHALL mencegah reply baru pada thread tersebut
3. WHEN moderator menghapus konten THEN the Forum System SHALL melakukan soft delete dan mencatat moderator yang menghapus
4. WHEN moderator mengedit konten THEN the Forum System SHALL menyimpan log perubahan dengan identitas moderator
5. WHEN pengguna non-moderator mencoba aksi moderasi THEN the Forum System SHALL menolak permintaan

### Requirement 7

**User Story:** Sebagai pengguna, saya ingin menerima notifikasi tentang aktivitas forum yang relevan, sehingga saya dapat tetap update dengan diskusi.

#### Acceptance Criteria

1. WHEN thread saya dibalas THEN the Forum System SHALL mengirim notifikasi kepada saya
2. WHEN reply saya dibalas THEN the Forum System SHALL mengirim notifikasi kepada saya
3. WHEN instruktur membuat thread THEN the Forum System SHALL mengirim notifikasi kepada semua asesi dalam skema
4. WHEN thread dipinned THEN the Forum System SHALL mengirim notifikasi kepada semua peserta skema
5. WHEN notifikasi dikirim THEN the Forum System SHALL menyimpan notifikasi dalam sistem notifikasi yang ada

### Requirement 8

**User Story:** Sebagai pengguna, saya ingin memberikan reaction pada thread atau reply, sehingga saya dapat menunjukkan apresiasi tanpa harus membalas.

#### Acceptance Criteria

1. WHEN pengguna memberikan reaction THEN the Forum System SHALL menyimpan reaction dengan tipe dan user ID
2. WHEN pengguna memberikan reaction yang sama dua kali THEN the Forum System SHALL menghapus reaction tersebut
3. WHEN menampilkan thread atau reply THEN the Forum System SHALL menampilkan jumlah setiap tipe reaction
4. WHEN pengguna memberikan reaction THEN the Forum System SHALL mendukung tipe reaction: like, helpful, solved
5. WHEN penulis menerima reaction THEN the Forum System SHALL mengirim notifikasi kepada penulis

### Requirement 9

**User Story:** Sebagai instruktur, saya ingin menandai reply sebagai jawaban yang benar, sehingga asesi dapat dengan mudah menemukan solusi.

#### Acceptance Criteria

1. WHEN instruktur menandai reply sebagai accepted answer THEN the Forum System SHALL menyimpan status accepted pada reply
2. WHEN reply ditandai sebagai accepted THEN the Forum System SHALL menampilkan reply di bagian atas dengan indikator khusus
3. WHEN thread memiliki accepted answer THEN the Forum System SHALL menandai thread sebagai resolved
4. WHEN instruktur menandai reply lain sebagai accepted THEN the Forum System SHALL menghapus status accepted dari reply sebelumnya
5. WHEN pengguna non-instruktur mencoba menandai accepted answer THEN the Forum System SHALL menolak permintaan

### Requirement 10

**User Story:** Sebagai sistem, saya ingin mencatat statistik forum, sehingga dapat memberikan insight tentang engagement pengguna.

#### Acceptance Criteria

1. WHEN thread dibuat atau dibalas THEN the Forum System SHALL update statistik aktivitas skema
2. WHEN menghitung statistik THEN the Forum System SHALL menyimpan jumlah thread, reply, dan view per skema
3. WHEN menghitung statistik pengguna THEN the Forum System SHALL menyimpan jumlah thread dan reply yang dibuat per pengguna
4. WHEN menampilkan statistik THEN the Forum System SHALL menghitung response rate dan average response time
5. WHEN statistik diakses THEN the Forum System SHALL menyediakan API endpoint untuk mengambil statistik forum
