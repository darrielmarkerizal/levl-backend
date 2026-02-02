<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\Common\Models\Audit;

class AuditService
{
    public function log(
        string $action,
        ?User $user,
        Request $request,
        $target = null,
        array $options = []
    ): Audit {
        $targetData = $this->extractTargetData($target);
        $actorData = $this->extractActorData($user);

        return Audit::create([
            'action' => $action,
            'actor_type' => $actorData['type'],
            'actor_id' => $actorData['id'],
            'user_id' => $user?->id,
            'target_table' => $targetData['table'],
            'target_type' => $targetData['type'],
            'target_id' => $targetData['id'],
            'module' => $options['module'] ?? null,
            'context' => $options['context'] ?? 'application',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => $options['meta'] ?? null,
            'properties' => $options['properties'] ?? null,
            'logged_at' => now(),
        ]);
    }

    public function logSystem(
        string $action,
        ?User $user,
        Request $request,
        $target = null,
        array $options = []
    ): Audit {
        $options['context'] = 'system';
        return $this->log($action, $user, $request, $target, $options);
    }

    public function logApplication(
        string $action,
        ?User $user,
        Request $request,
        $target = null,
        array $options = []
    ): Audit {
        $options['context'] = 'application';
        return $this->log($action, $user, $request, $target, $options);
    }

    private function extractTargetData($target): array
    {
        if (!$target) {
            return ['table' => null, 'type' => null, 'id' => null];
        }

        if (is_object($target) && method_exists($target, 'getTable')) {
            return [
                'table' => $target->getTable(),
                'type' => get_class($target),
                'id' => $target->id,
            ];
        }

        if (is_array($target)) {
            return [
                'table' => $target['table'] ?? null,
                'type' => $target['type'] ?? null,
                'id' => $target['id'] ?? null,
            ];
        }

        return ['table' => null, 'type' => null, 'id' => null];
    }

    private function extractActorData(?User $user): array
    {
        if (!$user) {
            return ['type' => null, 'id' => null];
        }

        return ['type' => get_class($user), 'id' => $user->id];
    }
}

