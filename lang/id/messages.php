<?php

return [
    'error' => 'Terjadi kesalahan.',
    'success' => 'Berhasil.',
    'unauthorized' => 'Anda tidak berhak mengakses resource ini.',
    'forbidden' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
    'not_found' => 'Resource yang Anda cari tidak ditemukan.',
    'invalid_request' => 'Permintaan tidak valid.',
    'validation_failed' => 'Data yang Anda kirim tidak valid. Periksa kembali isian Anda.',
    'server_error' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
    'unauthenticated' => 'Anda harus login terlebih dahulu.',
    'token_expired' => 'Token Anda telah kadaluarsa. Silakan login kembali.',
    'too_many_requests' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
    'method_not_allowed' => 'Metode HTTP tidak diizinkan untuk resource ini.',
    'conflict' => 'Permintaan Anda bertentangan dengan state resource yang ada.',
    'gone' => 'Resource yang Anda minta telah dihapus secara permanen.',

    // Common action messages
    'created' => 'Berhasil dibuat.',
    'updated' => 'Berhasil diperbarui.',
    'deleted' => 'Berhasil dihapus.',
    'restored' => 'Berhasil dipulihkan.',
    'archived' => 'Berhasil diarsipkan.',
    'published' => 'Berhasil dipublikasikan.',
    'unpublished' => 'Berhasil dibatalkan publikasinya.',
    'approved' => 'Berhasil disetujui.',
    'rejected' => 'Berhasil ditolak.',
    'sent' => 'Berhasil dikirim.',
    'saved' => 'Berhasil disimpan.',

    // Resource-specific messages
    'resource_created' => ':resource berhasil dibuat.',
    'resource_updated' => ':resource berhasil diperbarui.',
    'resource_deleted' => ':resource berhasil dihapus.',
    'resource_not_found' => ':resource tidak ditemukan.',

    // Authentication messages
    'login_success' => 'Login berhasil.',
    'logout_success' => 'Logout berhasil.',
    'password_changed' => 'Password berhasil diubah.',
    'password_reset_sent' => 'Link reset password telah dikirim ke email Anda.',
    'invalid_credentials' => 'Kredensial tidak valid.',
    'account_locked' => 'Akun Anda telah dikunci.',
    'account_inactive' => 'Akun Anda tidak aktif.',

    // Permission messages
    'permission_denied' => 'Izin ditolak.',
    'insufficient_permissions' => 'Anda tidak memiliki izin yang cukup.',
    'role_required' => 'Aksi ini memerlukan peran :role.',

    // Validation messages
    'invalid_input' => 'Input yang diberikan tidak valid.',
    'missing_required_field' => 'Field yang wajib diisi tidak ada.',
    'invalid_format' => 'Format tidak valid.',
    'value_too_long' => 'Nilai terlalu panjang.',
    'value_too_short' => 'Nilai terlalu pendek.',

    // File upload messages
    'file_uploaded' => 'File berhasil diunggah.',
    'file_upload_failed' => 'Gagal mengunggah file.',
    'file_too_large' => 'File terlalu besar.',
    'invalid_file_type' => 'Tipe file tidak valid.',

    // Database messages
    'duplicate_entry' => 'Entri duplikat.',
    'foreign_key_constraint' => 'Tidak dapat menghapus karena ada record terkait.',
    'database_error' => 'Terjadi kesalahan database.',

    // General messages
    'operation_successful' => 'Operasi berhasil diselesaikan.',
    'operation_failed' => 'Operasi gagal.',
    'no_changes_made' => 'Tidak ada perubahan yang dibuat.',
    'processing' => 'Memproses...',
    'please_wait' => 'Mohon tunggu...',
    'try_again' => 'Silakan coba lagi.',
    'contact_support' => 'Silakan hubungi dukungan jika masalah berlanjut.',
];
