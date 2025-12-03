<?php

namespace App\Exceptions;

/**
 * Exception thrown when a requested resource is not found.
 *
 * Returns HTTP 404 status code.
 */
class ResourceNotFoundException extends BusinessException
{
    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode = 404;

    /**
     * Application-specific error code.
     */
    protected string $errorCode = 'RESOURCE_NOT_FOUND';

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: __('messages.not_found'));
    }
}
