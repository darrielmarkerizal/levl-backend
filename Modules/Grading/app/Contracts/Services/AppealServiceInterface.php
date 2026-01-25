<?php

declare(strict_types=1);

namespace Modules\Grading\Contracts\Services;

use Illuminate\Support\Collection;
use Modules\Grading\Models\Appeal;

interface AppealServiceInterface
{
    public function submitAppeal(int $submissionId, string $reason, array $documents = []): Appeal;

    public function approveAppeal(int $appealId, int $instructorId): void;

    public function denyAppeal(int $appealId, int $instructorId, string $reason): void;

    public function getPendingAppeals(int $instructorId): Collection;
}
