<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when invalid sort fields are provided.
 *
 * Returns HTTP 400 status code.
 */
class InvalidSortException extends Exception
{
    /**
     * List of allowed sort fields.
     *
     * @var array<string>
     */
    protected array $allowedSorts;

    /**
     * The invalid sort field that was provided.
     */
    protected string $invalidSort;

    /**
     * Create a new exception instance.
     *
     * @param  string  $invalidSort  Invalid sort field provided
     * @param  array<string>  $allowedSorts  List of allowed sort fields
     */
    public function __construct(string $invalidSort, array $allowedSorts)
    {
        $this->invalidSort = $invalidSort;
        $this->allowedSorts = $allowedSorts;

        $message = sprintf(
            'Invalid sort field: %s. Allowed sorts: %s',
            $invalidSort,
            implode(', ', $allowedSorts)
        );

        parent::__construct($message, 400);
    }

    /**
     * Get the allowed sort fields.
     *
     * @return array<string>
     */
    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    /**
     * Get the invalid sort field.
     */
    public function getInvalidSort(): string
    {
        return $this->invalidSort;
    }
}
