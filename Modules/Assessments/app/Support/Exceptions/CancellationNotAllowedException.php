<?php

namespace Modules\Assessments\Support\Exceptions;

use App\Exceptions\BusinessException;

class CancellationNotAllowedException extends BusinessException
{
    protected $code = 422;

    public function __construct(string $message = 'Cancellation is not allowed at this time')
    {
        parent::__construct($message);
    }
}
