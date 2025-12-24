<?php

namespace Modules\Auth\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Contracts\Repositories\ProfileAuditLogRepositoryInterface;
use Modules\Auth\Models\ProfileAuditLog;

class ProfileAuditLogRepository implements ProfileAuditLogRepositoryInterface
{
    public function create(array $data): ProfileAuditLog
    {
        return ProfileAuditLog::create($data);
    }

    public function findByUserId(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return ProfileAuditLog::where('user_id', $userId)
            ->with('admin:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
