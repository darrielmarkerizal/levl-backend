<?php

namespace Modules\Content\Exceptions;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(string $message = 'Invalid workflow state transition')
    {
        parent::__construct($message);
    }
}
