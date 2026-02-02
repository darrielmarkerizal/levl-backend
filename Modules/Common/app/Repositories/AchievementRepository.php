<?php

declare(strict_types=1);

namespace Modules\Common\Repositories;

use App\Repositories\BaseRepository;
use Modules\Gamification\Models\Challenge;

class AchievementRepository extends BaseRepository
{
    protected function model(): string
    {
        return Challenge::class;
    }
}
