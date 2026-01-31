<?php

declare(strict_types=1);

namespace Modules\Enrollments\DTOs;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class UpdateEnrollmentDTO extends Data
{
    public function __construct(
        #[In(['active', 'pending', 'completed', 'cancelled'])]
        public string|Optional $status,

        public ?\DateTimeInterface $completedAt = null,
    ) {}
}
