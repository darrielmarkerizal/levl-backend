<?php

declare(strict_types=1);

namespace Modules\Grading\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Grading\Models\Appeal;

interface AppealRepositoryInterface
{
    public function create(array $data): Appeal;

    public function update(int $id, array $data): Appeal;

    public function findById(int $id): ?Appeal;

    public function findPending(): Collection;

    public function findBySubmission(int $submissionId): ?Appeal;

    public function findPendingForInstructor(int $instructorId): Collection;
}
