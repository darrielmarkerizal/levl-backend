<?php

namespace App\Exceptions;

use Exception;

/**
 * Base Business Exception class.
 *
 * All business logic exceptions should extend this class to ensure
 * consistent error handling and response formatting.
 */
abstract class BusinessException extends Exception
{
    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode = 400;

    /**
     * Application-specific error code.
     */
    protected string $errorCode = 'BUSINESS_ERROR';

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the application error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
