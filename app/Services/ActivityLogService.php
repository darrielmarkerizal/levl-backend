<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Repositories\ActivityLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityLogService
{
  public function __construct(private ActivityLogRepository $repository) {}

  /**
   * Get paginated activity logs.
   */
  public function paginate(int $perPage = 15): LengthAwarePaginator
  {
    return $this->repository->paginate([], $perPage);
  }

  /**
   * Get single activity log by ID.
   */
  public function find(int $id): ?ActivityLog
  {
    return $this->repository->find($id);
  }

  /**
   * Get distinct filter options for activity logs.
   */
  public function getFilterOptions(): array
  {
    return [
      'log_names' => ActivityLog::query()
        ->distinct()
        ->whereNotNull('log_name')
        ->pluck('log_name')
        ->filter()
        ->sort()
        ->values(),
      'browsers' => ActivityLog::query()
        ->distinct()
        ->whereNotNull('browser')
        ->pluck('browser')
        ->filter()
        ->sort()
        ->values(),
    ];
  }
}
