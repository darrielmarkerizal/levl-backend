<?php

declare(strict_types=1);

namespace Modules\Common\Repositories;

use App\Repositories\BaseRepository;
use Modules\Common\Models\LevelConfig;

class LevelConfigRepository extends BaseRepository
{
    protected function model(): string
    {
        return LevelConfig::class;
    }
}
