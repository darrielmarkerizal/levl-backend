<?php

declare(strict_types=1);

namespace Modules\Common\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Modules\Common\Contracts\Repositories\AuditRepositoryInterface;
use Modules\Common\Models\AuditLog;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class AuditRepository implements AuditRepositoryInterface
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    public function search(array $filters): Collection
    {
        $request = new Request(['filter' => $filters]);

        return QueryBuilder::for(AuditLog::class, $request)
            ->with(['actor', 'subject'])
            ->allowedFilters([
                AllowedFilter::exact('action'),
                AllowedFilter::exact('actor_id'),
                AllowedFilter::exact('actor_type'),
                AllowedFilter::exact('subject_id'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::callback('start_date', fn ($q, $v) => $q->where('created_at', '>=', $v)),
                AllowedFilter::callback('end_date', fn ($q, $v) => $q->where('created_at', '<=', $v)),
                AllowedFilter::callback('context_search', fn ($q, $v) => $q->where('context', 'like', "%{$v}%")),
            ])
            ->allowedSorts(['created_at', 'action', 'actor_id'])
            ->defaultSort('-created_at')
            ->get();
    }

    /**
     * Find audit logs for a specific subject.
     *
     * @param  string  $subjectType  The subject type (model class)
     * @param  int  $subjectId  The subject ID
     * @return Collection<int, AuditLog> Collection of audit logs for the subject
     */
    public function findBySubject(string $subjectType, int $subjectId): Collection
    {
        return AuditLog::query()
            ->with(['actor', 'subject'])
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find audit logs by actor.
     *
     * @param  string  $actorType  The actor type (model class)
     * @param  int  $actorId  The actor ID
     * @return Collection<int, AuditLog> Collection of audit logs by the actor
     */
    public function findByActor(string $actorType, int $actorId): Collection
    {
        return AuditLog::query()
            ->with(['actor', 'subject'])
            ->where('actor_type', $actorType)
            ->where('actor_id', $actorId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find audit logs by action type.
     *
     * @param  string  $action  The action type
     * @return Collection<int, AuditLog> Collection of audit logs for the action
     */
    public function findByAction(string $action): Collection
    {
        return AuditLog::query()
            ->with(['actor', 'subject'])
            ->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
