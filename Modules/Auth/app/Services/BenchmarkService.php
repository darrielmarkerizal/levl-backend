<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Repositories\BenchmarkRepository;

class BenchmarkService
{
    public function __construct(
        private readonly BenchmarkRepository $repository
    ) {}

    /**
     * Get 1000 users for benchmark READ operation.
     */
    public function getBenchmarkUsers(): Collection
    {
        return $this->repository->get1000Users();
    }

    /**
     * Create 1000 users for benchmark CREATE operation.
     * Uses static data generation for consistency.
     */
    public function createBenchmarkUsers(): bool
    {
        $users = [];
        $password = Hash::make('password'); // Pre-hash for performance consistency or hash per user? 
        // Research says "Logika Identik" (Identical Logic). 
        // If real app hashes password, we should probably hash it or use a pre-hashed one if the goal is DB insert speed mainly.
        // But requested "Operasi CREATE (1000 baris data)".
        // "Data Statis... menjaga konsistensi format".
        
        // Generating 1000 rows array
        // We'll use a fixed timestamp to avoid time drift affecting benchmark slightly? No, current time is fine.
        $now = now();
        
        for ($i = 0; $i < 1000; $i++) {
            $users[] = [
                'name' => 'Benchmark User ' . Str::random(10),
                'username' => 'bench_' . Str::random(10) . '_' . $i, // Ensure unique
                'email' => 'bench_' . Str::random(10) . '_' . $i . '@example.com',
                'password' => $password,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Chunking is usually handled by db driver but insert() can take large valid array.
        // 1000 rows is fine for single insert in most modern DBs.
        return $this->repository->insert1000Users($users);
    }
}
