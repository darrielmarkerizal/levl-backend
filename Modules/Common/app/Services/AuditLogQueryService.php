<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorInstance;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Common\Models\AuditLog;
use Modules\Common\Services\AssessmentAuditService;

class AuditLogQueryService
{
    public function __construct(
        private readonly AuditServiceInterface $auditService
    ) {}

    public function canAccess(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasRole('Admin') || $user->hasRole('Superadmin');
    }

    public function searchAndPaginate(array $validated): array
    {
        $filters = $this->buildFilters($validated);
        $auditLogs = $this->auditService->search($filters);
        
        return $this->paginateResults($auditLogs, $validated);
    }

    public function findById(int $id): ?AuditLog
    {
        return AuditLog::find($id);
    }

    public function getAvailableActions(): array
    {
        return [
            AssessmentAuditService::ACTION_SUBMISSION_CREATED,
            AssessmentAuditService::ACTION_STATE_TRANSITION,
            AssessmentAuditService::ACTION_GRADING,
            AssessmentAuditService::ACTION_ANSWER_KEY_CHANGE,
            AssessmentAuditService::ACTION_GRADE_OVERRIDE,
            AssessmentAuditService::ACTION_OVERRIDE_GRANT,
        ];
    }

    private function buildFilters(array $validated): array
    {
        $filterKeys = [
            'action', 'actions', 'actor_id', 'actor_type', 
            'subject_id', 'subject_type', 'start_date', 'end_date',
            'context_search', 'assignment_id', 'student_id'
        ];

        $filters = [];
        foreach ($filterKeys as $key) {
            if (isset($validated[$key])) {
                $filters[$key] = $validated[$key];
            }
        }

        return $filters;
    }

    private function paginateResults(Collection $auditLogs, array $validated): array
    {
        $page = $this->extractPage($validated);
        $perPage = $this->extractPerPage($validated);
        
        return [
            'logs' => $this->sliceLogs($auditLogs, $page, $perPage),
            'meta' => $this->buildMeta($auditLogs->count(), $page, $perPage),
        ];
    }

    private function sliceLogs(Collection $auditLogs, int $page, int $perPage): Collection
    {
        return $auditLogs->slice(($page - 1) * $perPage, $perPage)->values();
    }

    private function buildMeta(int $total, int $page, int $perPage): array
    {
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    private function extractPage(array $validated): int
    {
        if (! isset($validated['page'])) {
            return 1;
        }

        return is_numeric($validated['page']) ? intval($validated['page']) : 1;
    }

    private function extractPerPage(array $validated): int
    {
        if (! isset($validated['per_page'])) {
            return 15;
        }

        return is_numeric($validated['per_page']) ? intval($validated['per_page']) : 15;
    }
}
