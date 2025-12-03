<?php

namespace App\Exceptions;

/**
 * Exception thrown when password validation fails.
 *
 * Returns HTTP 400 status code.
 */
class InvalidPasswordException extends BusinessException
{
    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode = 400;

    /**
     * Application-specific error code.
     */
    protected string $errorCode = 'INVALID_PASSWORD';

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: __('validation.current_password'));
    }
}
