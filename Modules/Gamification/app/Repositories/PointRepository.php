<?php

namespace Modules\Gamification\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Gamification\Contracts\Repositories\PointRepositoryInterface;
use Modules\Gamification\Models\Point;

class PointRepository implements PointRepositoryInterface
{
    public function paginateByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Point::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        return Point::create($data);
    }

    public function sumByUserId(int $userId): int
    {
        return Point::where('user_id', $userId)->sum('points');
    }
}
