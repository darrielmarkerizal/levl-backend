<?php

return [
    // HTTP Error Messages
    '400' => 'Bad Request',
    '401' => 'Unauthorized',
    '403' => 'Forbidden',
    '404' => 'Not Found',
    '405' => 'Method Not Allowed',
    '409' => 'Conflict',
    '410' => 'Gone',
    '422' => 'Unprocessable Entity',
    '429' => 'Too Many Requests',
    '500' => 'Internal Server Error',
    '502' => 'Bad Gateway',
    '503' => 'Service Unavailable',
    '504' => 'Gateway Timeout',

    // Custom Error Messages
    'server_error' => 'An internal server error occurred. Please try again later.',
    'not_found' => 'The requested resource was not found.',
    'unauthorized' => 'You are not authorized to access this resource.',
    'forbidden' => 'You do not have permission to perform this action.',
    'validation_error' => 'The provided data is invalid.',
    'duplicate_resource' => 'This resource already exists.',
    'invalid_password' => 'The provided password is invalid.',
    'resource_not_found' => 'The requested :resource was not found.',
    'resource_already_exists' => 'The :resource already exists.',

    // Business Logic Errors
    'invalid_operation' => 'This operation is not allowed.',
    'operation_not_permitted' => 'You are not permitted to perform this operation.',
    'resource_locked' => 'This resource is currently locked.',
    'resource_expired' => 'This resource has expired.',
    'quota_exceeded' => 'You have exceeded your quota.',
    'dependency_exists' => 'Cannot delete this resource because it has dependencies.',
    'invalid_state' => 'The resource is in an invalid state for this operation.',
    'concurrent_modification' => 'The resource was modified by another user.',

    // Authentication Errors
    'invalid_credentials' => 'The provided credentials are invalid.',
    'account_locked' => 'Your account has been locked.',
    'account_inactive' => 'Your account is inactive.',
    'account_suspended' => 'Your account has been suspended.',
    'token_expired' => 'Your authentication token has expired.',
    'token_invalid' => 'Your authentication token is invalid.',
    'session_expired' => 'Your session has expired. Please log in again.',
    'email_not_verified' => 'Your email address has not been verified.',

    // Authorization Errors
    'insufficient_permissions' => 'You do not have sufficient permissions to perform this action.',
    'role_required' => 'This action requires the :role role.',
    'permission_denied' => 'Permission denied.',
    'access_denied' => 'Access denied.',

    // Validation Errors
    'invalid_input' => 'The provided input is invalid.',
    'missing_required_field' => 'A required field is missing.',
    'invalid_format' => 'The format is invalid.',
    'value_out_of_range' => 'The value is out of the acceptable range.',
    'invalid_date_range' => 'The date range is invalid.',
    'invalid_file_type' => 'The file type is not allowed.',
    'file_too_large' => 'The file is too large.',
    'file_upload_failed' => 'The file upload failed.',

    // Database Errors
    'database_error' => 'A database error occurred.',
    'connection_failed' => 'Database connection failed.',
    'query_failed' => 'Database query failed.',
    'transaction_failed' => 'Database transaction failed.',
    'duplicate_entry' => 'A duplicate entry was detected.',
    'foreign_key_constraint' => 'Cannot perform this operation due to related records.',
    'integrity_constraint' => 'Database integrity constraint violation.',

    // External Service Errors
    'external_service_error' => 'An external service error occurred.',
    'api_error' => 'An API error occurred.',
    'network_error' => 'A network error occurred.',
    'timeout_error' => 'The operation timed out.',
    'service_unavailable' => 'The service is currently unavailable.',

    // File System Errors
    'file_not_found' => 'The file was not found.',
    'file_read_error' => 'Failed to read the file.',
    'file_write_error' => 'Failed to write to the file.',
    'directory_not_found' => 'The directory was not found.',
    'permission_denied_file' => 'Permission denied to access the file.',

    // Rate Limiting Errors
    'rate_limit_exceeded' => 'You have exceeded the rate limit. Please try again later.',
    'too_many_attempts' => 'Too many attempts. Please try again later.',
    'throttled' => 'Too many requests. Please slow down.',

    // Generic Errors
    'unknown_error' => 'An unknown error occurred.',
    'unexpected_error' => 'An unexpected error occurred.',
    'operation_failed' => 'The operation failed.',
    'processing_error' => 'An error occurred while processing your request.',
    'configuration_error' => 'A configuration error occurred.',
    'maintenance_mode' => 'The application is currently in maintenance mode.',
];
