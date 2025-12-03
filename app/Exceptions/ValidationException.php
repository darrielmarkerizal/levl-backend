<?php

namespace App\Exceptions;

/**
 * Exception thrown when validation fails.
 *
 * Returns HTTP 422 status code.
 */
class ValidationException extends BusinessException
{
    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode = 422;

    /**
     * Application-specific error code.
     */
    protected string $errorCode = 'VALIDATION_ERROR';

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: __('messages.validation_failed'));
    }
}
