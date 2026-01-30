<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;

class BenchmarkRepository extends BaseRepository
{
    public function model(): string
    {
        return User::class;
    }

    /**
     * Retrieve 1000 users for benchmark.
     */
    public function get1000Users(): Collection
    {
        return $this->model->newQuery()
            ->limit(1000)
            ->get();
    }

    /**
     * Batch insert 1000 users.
     */
    public function insert1000Users(array $data): bool
    {
        return $this->model->newQuery()->insert($data);
    }
}
