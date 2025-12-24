<?php

namespace Modules\Auth\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\ProfileAuditLog;

interface ProfileAuditLogRepositoryInterface
{
    /**
     * Create a new profile audit log entry
     */
    public function create(array $data): ProfileAuditLog;

    /**
     * Find audit logs by user ID with pagination
     */
    public function findByUserId(int $userId, int $perPage = 20): LengthAwarePaginator;
}
