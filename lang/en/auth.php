<?php

return [
    // Authentication Messages
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // Login Messages
    'login_success' => 'You have successfully logged in.',
    'login_failed' => 'Login failed. Please check your credentials.',
    'logout_success' => 'You have successfully logged out.',

    // Registration Messages
    'registration_success' => 'Registration successful. Please verify your email.',
    'registration_failed' => 'Registration failed. Please try again.',
    'email_already_registered' => 'This email address is already registered.',
    'username_already_taken' => 'This username is already taken.',

    // Password Reset Messages
    'password_reset_sent' => 'Password reset link has been sent to your email.',
    'password_reset_success' => 'Your password has been reset successfully.',
    'password_reset_failed' => 'Password reset failed. Please try again.',
    'password_reset_token_invalid' => 'This password reset token is invalid.',
    'password_reset_token_expired' => 'This password reset token has expired.',

    // Password Change Messages
    'password_changed' => 'Your password has been changed successfully.',
    'password_change_failed' => 'Password change failed. Please try again.',
    'current_password_incorrect' => 'Your current password is incorrect.',
    'new_password_same_as_old' => 'Your new password must be different from your current password.',

    // Email Verification Messages
    'email_verification_sent' => 'Verification email has been sent.',
    'email_verified' => 'Your email has been verified successfully.',
    'email_verification_failed' => 'Email verification failed.',
    'email_already_verified' => 'Your email is already verified.',
    'email_not_verified' => 'Please verify your email address.',

    // Account Status Messages
    'account_locked' => 'Your account has been locked. Please contact support.',
    'account_inactive' => 'Your account is inactive. Please contact support.',
    'account_suspended' => 'Your account has been suspended.',
    'account_deleted' => 'Your account has been deleted.',
    'account_activated' => 'Your account has been activated.',

    // Token Messages
    'token_expired' => 'Your authentication token has expired. Please log in again.',
    'token_invalid' => 'Your authentication token is invalid.',
    'token_missing' => 'Authentication token is missing.',
    'token_refresh_success' => 'Token refreshed successfully.',
    'token_refresh_failed' => 'Token refresh failed.',

    // Session Messages
    'session_expired' => 'Your session has expired. Please log in again.',
    'session_invalid' => 'Your session is invalid.',
    'concurrent_session_detected' => 'Your account is being used on another device.',

    // Two-Factor Authentication Messages
    '2fa_enabled' => 'Two-factor authentication has been enabled.',
    '2fa_disabled' => 'Two-factor authentication has been disabled.',
    '2fa_code_sent' => 'Two-factor authentication code has been sent.',
    '2fa_code_invalid' => 'The two-factor authentication code is invalid.',
    '2fa_code_expired' => 'The two-factor authentication code has expired.',
    '2fa_required' => 'Two-factor authentication is required.',

    // Permission Messages
    'unauthorized' => 'You are not authorized to access this resource.',
    'forbidden' => 'You do not have permission to perform this action.',
    'insufficient_permissions' => 'You do not have sufficient permissions.',
    'role_required' => 'This action requires the :role role.',

    // Profile Messages
    'profile_updated' => 'Your profile has been updated successfully.',
    'profile_update_failed' => 'Profile update failed. Please try again.',
    'avatar_uploaded' => 'Your avatar has been uploaded successfully.',
    'avatar_upload_failed' => 'Avatar upload failed. Please try again.',
    'avatar_removed' => 'Your avatar has been removed.',

    // Security Messages
    'security_question_set' => 'Security question has been set.',
    'security_answer_incorrect' => 'Security answer is incorrect.',
    'suspicious_activity_detected' => 'Suspicious activity detected. Please verify your identity.',
    'ip_blocked' => 'Your IP address has been blocked.',
    'device_not_recognized' => 'Device not recognized. Please verify your identity.',

    // API Key Messages
    'api_key_created' => 'API key created successfully.',
    'api_key_revoked' => 'API key revoked successfully.',
    'api_key_invalid' => 'The API key is invalid.',
    'api_key_expired' => 'The API key has expired.',
];
