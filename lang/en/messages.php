<?php

return [
    'error' => 'An error occurred.',
    'success' => 'Success.',
    'unauthorized' => 'You are not authorized to access this resource.',
    'forbidden' => 'You do not have permission to perform this action.',
    'not_found' => 'The resource you are looking for was not found.',
    'invalid_request' => 'Invalid request.',
    'validation_failed' => 'The data you submitted is invalid. Please check your input.',
    'server_error' => 'A server error occurred. Please try again later.',
    'unauthenticated' => 'You must be logged in.',
    'token_expired' => 'Your token has expired. Please log in again.',
    'too_many_requests' => 'Too many requests. Please try again later.',
    'method_not_allowed' => 'HTTP method not allowed for this resource.',
    'conflict' => 'Your request conflicts with the current state of the resource.',
    'gone' => 'The resource you requested has been permanently deleted.',

    // Common action messages
    'created' => 'Created successfully.',
    'updated' => 'Updated successfully.',
    'deleted' => 'Deleted successfully.',
    'restored' => 'Restored successfully.',
    'archived' => 'Archived successfully.',
    'published' => 'Published successfully.',
    'unpublished' => 'Unpublished successfully.',
    'approved' => 'Approved successfully.',
    'rejected' => 'Rejected successfully.',
    'sent' => 'Sent successfully.',
    'saved' => 'Saved successfully.',

    // Resource-specific messages
    'resource_created' => ':resource created successfully.',
    'resource_updated' => ':resource updated successfully.',
    'resource_deleted' => ':resource deleted successfully.',
    'resource_not_found' => ':resource not found.',

    // Authentication messages
    'login_success' => 'Login successful.',
    'logout_success' => 'Logout successful.',
    'password_changed' => 'Password changed successfully.',
    'password_reset_sent' => 'Password reset link sent to your email.',
    'invalid_credentials' => 'Invalid credentials.',
    'account_locked' => 'Your account has been locked.',
    'account_inactive' => 'Your account is inactive.',

    // Permission messages
    'permission_denied' => 'Permission denied.',
    'insufficient_permissions' => 'You do not have sufficient permissions.',
    'role_required' => 'This action requires :role role.',

    // Validation messages
    'invalid_input' => 'Invalid input provided.',
    'missing_required_field' => 'Required field is missing.',
    'invalid_format' => 'Invalid format.',
    'value_too_long' => 'Value is too long.',
    'value_too_short' => 'Value is too short.',

    // File upload messages
    'file_uploaded' => 'File uploaded successfully.',
    'file_upload_failed' => 'File upload failed.',
    'file_too_large' => 'File is too large.',
    'invalid_file_type' => 'Invalid file type.',

    // Database messages
    'duplicate_entry' => 'Duplicate entry.',
    'foreign_key_constraint' => 'Cannot delete due to related records.',
    'database_error' => 'Database error occurred.',

    // General messages
    'operation_successful' => 'Operation completed successfully.',
    'operation_failed' => 'Operation failed.',
    'no_changes_made' => 'No changes were made.',
    'processing' => 'Processing...',
    'please_wait' => 'Please wait...',
    'try_again' => 'Please try again.',
    'contact_support' => 'Please contact support if the problem persists.',
];
