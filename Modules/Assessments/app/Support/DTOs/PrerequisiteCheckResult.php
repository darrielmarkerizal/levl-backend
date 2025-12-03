<?php

namespace Modules\Assessments\Support\DTOs;

class PrerequisiteCheckResult
{
    public function __construct(
        public readonly bool $met,
        public readonly array $missingPrerequisites = [],
        public readonly array $completedPrerequisites = []
    ) {}

    public function toArray(): array
    {
        return [
            'prerequisites_met' => $this->met,
            'missing_prerequisites' => $this->missingPrerequisites,
            'completed_prerequisites' => $this->completedPrerequisites,
        ];
    }
}
