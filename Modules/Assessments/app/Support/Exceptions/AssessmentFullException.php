<?php

namespace Modules\Assessments\Support\Exceptions;

use App\Exceptions\BusinessException;

class AssessmentFullException extends BusinessException
{
    protected $code = 422;

    public function __construct(string $message = 'Assessment slot is full')
    {
        parent::__construct($message);
    }
}
