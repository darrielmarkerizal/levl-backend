<?php

namespace App\Exceptions;

/**
 * Exception thrown when user is not authenticated.
 *
 * Returns HTTP 401 status code.
 */
class UnauthorizedException extends BusinessException
{
    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode = 401;

    /**
     * Application-specific error code.
     */
    protected string $errorCode = 'UNAUTHORIZED';

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: __('messages.unauthenticated'));
    }
}
