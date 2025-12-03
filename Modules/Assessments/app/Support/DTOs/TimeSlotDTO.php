<?php

namespace Modules\Assessments\Support\DTOs;

use Carbon\Carbon;

class TimeSlotDTO
{
    public function __construct(
        public readonly Carbon $datetime,
        public readonly int $maxCapacity,
        public readonly int $currentBookings,
        public readonly int $availableCapacity,
        public readonly bool $available
    ) {}

    public function toArray(): array
    {
        return [
            'datetime' => $this->datetime->toIso8601String(),
            'max_capacity' => $this->maxCapacity,
            'current_bookings' => $this->currentBookings,
            'available_capacity' => $this->availableCapacity,
            'available' => $this->available,
        ];
    }
}
