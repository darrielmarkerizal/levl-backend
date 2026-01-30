<?php

declare(strict_types=1);

namespace Modules\Benchmark\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Models\User;

class BenchmarkRepository
{
    /**
     * Fetch a single random user for light workload simulation.
     */
    public function getLightData(): ?User
    {
        return User::orderBy('id')->first();
    }

    /**
     * Fetch all users with their roles for heavy workload simulation.
     * We will process this data in memory to simulate high CPU usage.
     */
    public function getHeavyData(): Collection
    {
        return User::with('roles')->limit(500)->get();
    }
}
