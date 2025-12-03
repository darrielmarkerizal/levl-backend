<?php

namespace App\Exceptions;

/**
 * Exception thrown when attempting to create a duplicate resource.
 *
 * Returns HTTP 409 status code.
 */
class DuplicateResourceException extends BusinessException
{
    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode = 409;

    /**
     * Application-specific error code.
     */
    protected string $errorCode = 'DUPLICATE_RESOURCE';

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: __('messages.conflict'));
    }
}
