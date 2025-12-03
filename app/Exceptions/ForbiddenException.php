<?php

namespace App\Exceptions;

/**
 * Exception thrown when user is authenticated but lacks permission.
 *
 * Returns HTTP 403 status code.
 */
class ForbiddenException extends BusinessException
{
    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode = 403;

    /**
     * Application-specific error code.
     */
    protected string $errorCode = 'FORBIDDEN';

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: __('messages.forbidden'));
    }
}
