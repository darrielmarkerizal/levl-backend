<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when invalid filter fields are provided.
 *
 * Returns HTTP 400 status code.
 */
class InvalidFilterException extends Exception
{
    /**
     * List of allowed filter fields.
     *
     * @var array<string>
     */
    protected array $allowedFilters;

    /**
     * List of invalid filter fields that were provided.
     *
     * @var array<string>
     */
    protected array $invalidFilters;

    /**
     * Create a new exception instance.
     *
     * @param  array<string>  $invalidFilters  Invalid filter fields provided
     * @param  array<string>  $allowedFilters  List of allowed filter fields
     */
    public function __construct(array $invalidFilters, array $allowedFilters)
    {
        $this->invalidFilters = $invalidFilters;
        $this->allowedFilters = $allowedFilters;

        $message = sprintf(
            'Invalid filter fields: %s. Allowed filters: %s',
            implode(', ', $invalidFilters),
            implode(', ', $allowedFilters)
        );

        parent::__construct($message, 400);
    }

    /**
     * Get the allowed filter fields.
     *
     * @return array<string>
     */
    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    /**
     * Get the invalid filter fields.
     *
     * @return array<string>
     */
    public function getInvalidFilters(): array
    {
        return $this->invalidFilters;
    }
}
