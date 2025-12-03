<?php

return [
    // HTTP Error Messages
    '400' => 'Permintaan Buruk',
    '401' => 'Tidak Terotorisasi',
    '403' => 'Terlarang',
    '404' => 'Tidak Ditemukan',
    '405' => 'Metode Tidak Diizinkan',
    '409' => 'Konflik',
    '410' => 'Hilang',
    '422' => 'Entitas Tidak Dapat Diproses',
    '429' => 'Terlalu Banyak Permintaan',
    '500' => 'Kesalahan Server Internal',
    '502' => 'Gateway Buruk',
    '503' => 'Layanan Tidak Tersedia',
    '504' => 'Timeout Gateway',

    // Custom Error Messages
    'server_error' => 'Terjadi kesalahan server internal. Silakan coba lagi nanti.',
    'not_found' => 'Resource yang diminta tidak ditemukan.',
    'unauthorized' => 'Anda tidak terotorisasi untuk mengakses resource ini.',
    'forbidden' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
    'validation_error' => 'Data yang diberikan tidak valid.',
    'duplicate_resource' => 'Resource ini sudah ada.',
    'invalid_password' => 'Password yang diberikan tidak valid.',
    'resource_not_found' => ':resource yang diminta tidak ditemukan.',
    'resource_already_exists' => ':resource sudah ada.',

    // Business Logic Errors
    'invalid_operation' => 'Operasi ini tidak diizinkan.',
    'operation_not_permitted' => 'Anda tidak diizinkan untuk melakukan operasi ini.',
    'resource_locked' => 'Resource ini sedang dikunci.',
    'resource_expired' => 'Resource ini telah kadaluarsa.',
    'quota_exceeded' => 'Anda telah melebihi kuota Anda.',
    'dependency_exists' => 'Tidak dapat menghapus resource ini karena memiliki dependensi.',
    'invalid_state' => 'Resource dalam keadaan tidak valid untuk operasi ini.',
    'concurrent_modification' => 'Resource telah dimodifikasi oleh pengguna lain.',

    // Authentication Errors
    'invalid_credentials' => 'Kredensial yang diberikan tidak valid.',
    'account_locked' => 'Akun Anda telah dikunci.',
    'account_inactive' => 'Akun Anda tidak aktif.',
    'account_suspended' => 'Akun Anda telah ditangguhkan.',
    'token_expired' => 'Token autentikasi Anda telah kadaluarsa.',
    'token_invalid' => 'Token autentikasi Anda tidak valid.',
    'session_expired' => 'Sesi Anda telah kadaluarsa. Silakan login kembali.',
    'email_not_verified' => 'Alamat email Anda belum diverifikasi.',

    // Authorization Errors
    'insufficient_permissions' => 'Anda tidak memiliki izin yang cukup untuk melakukan aksi ini.',
    'role_required' => 'Aksi ini memerlukan peran :role.',
    'permission_denied' => 'Izin ditolak.',
    'access_denied' => 'Akses ditolak.',

    // Validation Errors
    'invalid_input' => 'Input yang diberikan tidak valid.',
    'missing_required_field' => 'Field yang wajib diisi tidak ada.',
    'invalid_format' => 'Format tidak valid.',
    'value_out_of_range' => 'Nilai di luar rentang yang dapat diterima.',
    'invalid_date_range' => 'Rentang tanggal tidak valid.',
    'invalid_file_type' => 'Tipe file tidak diizinkan.',
    'file_too_large' => 'File terlalu besar.',
    'file_upload_failed' => 'Gagal mengunggah file.',

    // Database Errors
    'database_error' => 'Terjadi kesalahan database.',
    'connection_failed' => 'Koneksi database gagal.',
    'query_failed' => 'Query database gagal.',
    'transaction_failed' => 'Transaksi database gagal.',
    'duplicate_entry' => 'Entri duplikat terdeteksi.',
    'foreign_key_constraint' => 'Tidak dapat melakukan operasi ini karena ada record terkait.',
    'integrity_constraint' => 'Pelanggaran constraint integritas database.',

    // External Service Errors
    'external_service_error' => 'Terjadi kesalahan layanan eksternal.',
    'api_error' => 'Terjadi kesalahan API.',
    'network_error' => 'Terjadi kesalahan jaringan.',
    'timeout_error' => 'Operasi timeout.',
    'service_unavailable' => 'Layanan saat ini tidak tersedia.',

    // File System Errors
    'file_not_found' => 'File tidak ditemukan.',
    'file_read_error' => 'Gagal membaca file.',
    'file_write_error' => 'Gagal menulis ke file.',
    'directory_not_found' => 'Direktori tidak ditemukan.',
    'permission_denied_file' => 'Izin ditolak untuk mengakses file.',

    // Rate Limiting Errors
    'rate_limit_exceeded' => 'Anda telah melebihi batas rate. Silakan coba lagi nanti.',
    'too_many_attempts' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.',
    'throttled' => 'Terlalu banyak permintaan. Silakan perlambat.',

    // Generic Errors
    'unknown_error' => 'Terjadi kesalahan yang tidak diketahui.',
    'unexpected_error' => 'Terjadi kesalahan yang tidak terduga.',
    'operation_failed' => 'Operasi gagal.',
    'processing_error' => 'Terjadi kesalahan saat memproses permintaan Anda.',
    'configuration_error' => 'Terjadi kesalahan konfigurasi.',
    'maintenance_mode' => 'Aplikasi saat ini dalam mode pemeliharaan.',
];
