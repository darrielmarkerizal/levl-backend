<?php

declare(strict_types=1);

namespace Modules\Common\Repositories;

use App\Repositories\BaseRepository;
use Modules\Gamification\Models\Badge;

class BadgeRepository extends BaseRepository
{
    protected function model(): string
    {
        return Badge::class;
    }
}
