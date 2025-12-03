<?php

return [
    // Authentication Messages
    'failed' => 'Kredensial ini tidak cocok dengan catatan kami.',
    'password' => 'Password yang diberikan salah.',
    'throttle' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam :seconds detik.',

    // Login Messages
    'login_success' => 'Anda berhasil login.',
    'login_failed' => 'Login gagal. Silakan periksa kredensial Anda.',
    'logout_success' => 'Anda berhasil logout.',

    // Registration Messages
    'registration_success' => 'Registrasi berhasil. Silakan verifikasi email Anda.',
    'registration_failed' => 'Registrasi gagal. Silakan coba lagi.',
    'email_already_registered' => 'Alamat email ini sudah terdaftar.',
    'username_already_taken' => 'Username ini sudah digunakan.',

    // Password Reset Messages
    'password_reset_sent' => 'Link reset password telah dikirim ke email Anda.',
    'password_reset_success' => 'Password Anda berhasil direset.',
    'password_reset_failed' => 'Reset password gagal. Silakan coba lagi.',
    'password_reset_token_invalid' => 'Token reset password ini tidak valid.',
    'password_reset_token_expired' => 'Token reset password ini telah kadaluarsa.',

    // Password Change Messages
    'password_changed' => 'Password Anda berhasil diubah.',
    'password_change_failed' => 'Perubahan password gagal. Silakan coba lagi.',
    'current_password_incorrect' => 'Password saat ini Anda salah.',
    'new_password_same_as_old' => 'Password baru Anda harus berbeda dari password saat ini.',

    // Email Verification Messages
    'email_verification_sent' => 'Email verifikasi telah dikirim.',
    'email_verified' => 'Email Anda berhasil diverifikasi.',
    'email_verification_failed' => 'Verifikasi email gagal.',
    'email_already_verified' => 'Email Anda sudah diverifikasi.',
    'email_not_verified' => 'Silakan verifikasi alamat email Anda.',

    // Account Status Messages
    'account_locked' => 'Akun Anda telah dikunci. Silakan hubungi dukungan.',
    'account_inactive' => 'Akun Anda tidak aktif. Silakan hubungi dukungan.',
    'account_suspended' => 'Akun Anda telah ditangguhkan.',
    'account_deleted' => 'Akun Anda telah dihapus.',
    'account_activated' => 'Akun Anda telah diaktifkan.',

    // Token Messages
    'token_expired' => 'Token autentikasi Anda telah kadaluarsa. Silakan login kembali.',
    'token_invalid' => 'Token autentikasi Anda tidak valid.',
    'token_missing' => 'Token autentikasi tidak ada.',
    'token_refresh_success' => 'Token berhasil diperbarui.',
    'token_refresh_failed' => 'Pembaruan token gagal.',

    // Session Messages
    'session_expired' => 'Sesi Anda telah kadaluarsa. Silakan login kembali.',
    'session_invalid' => 'Sesi Anda tidak valid.',
    'concurrent_session_detected' => 'Akun Anda sedang digunakan di perangkat lain.',

    // Two-Factor Authentication Messages
    '2fa_enabled' => 'Autentikasi dua faktor telah diaktifkan.',
    '2fa_disabled' => 'Autentikasi dua faktor telah dinonaktifkan.',
    '2fa_code_sent' => 'Kode autentikasi dua faktor telah dikirim.',
    '2fa_code_invalid' => 'Kode autentikasi dua faktor tidak valid.',
    '2fa_code_expired' => 'Kode autentikasi dua faktor telah kadaluarsa.',
    '2fa_required' => 'Autentikasi dua faktor diperlukan.',

    // Permission Messages
    'unauthorized' => 'Anda tidak terotorisasi untuk mengakses resource ini.',
    'forbidden' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
    'insufficient_permissions' => 'Anda tidak memiliki izin yang cukup.',
    'role_required' => 'Aksi ini memerlukan peran :role.',

    // Profile Messages
    'profile_updated' => 'Profil Anda berhasil diperbarui.',
    'profile_update_failed' => 'Pembaruan profil gagal. Silakan coba lagi.',
    'avatar_uploaded' => 'Avatar Anda berhasil diunggah.',
    'avatar_upload_failed' => 'Gagal mengunggah avatar. Silakan coba lagi.',
    'avatar_removed' => 'Avatar Anda telah dihapus.',

    // Security Messages
    'security_question_set' => 'Pertanyaan keamanan telah diatur.',
    'security_answer_incorrect' => 'Jawaban keamanan salah.',
    'suspicious_activity_detected' => 'Aktivitas mencurigakan terdeteksi. Silakan verifikasi identitas Anda.',
    'ip_blocked' => 'Alamat IP Anda telah diblokir.',
    'device_not_recognized' => 'Perangkat tidak dikenali. Silakan verifikasi identitas Anda.',

    // API Key Messages
    'api_key_created' => 'API key berhasil dibuat.',
    'api_key_revoked' => 'API key berhasil dicabut.',
    'api_key_invalid' => 'API key tidak valid.',
    'api_key_expired' => 'API key telah kadaluarsa.',
];
