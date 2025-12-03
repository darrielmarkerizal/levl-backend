<?php

namespace Modules\Assessments\Support\Exceptions;

use App\Exceptions\BusinessException;

class PrerequisitesNotMetException extends BusinessException
{
    protected $code = 422;

    public function __construct(array $missingPrerequisites = [])
    {
        $message = 'Prerequisites not met for this assessment.';

        if (! empty($missingPrerequisites)) {
            $message .= ' Missing: '.implode(', ', $missingPrerequisites);
        }

        parent::__construct($message);

        $this->data = [
            'missing_prerequisites' => $missingPrerequisites,
        ];
    }
}
