<?php

namespace Modules\Assessments\Support\DTOs;

class RegisterAssessmentDTO
{
    public function __construct(
        public readonly ?int $enrollmentId = null,
        public readonly ?string $scheduledAt = null,
        public readonly ?float $paymentAmount = null,
        public readonly ?string $notes = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enrollmentId: $data['enrollment_id'] ?? null,
            scheduledAt: $data['scheduled_at'] ?? null,
            paymentAmount: $data['payment_amount'] ?? null,
            notes: $data['notes'] ?? null
        );
    }
}
