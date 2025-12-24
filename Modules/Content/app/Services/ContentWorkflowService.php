<?php

namespace Modules\Content\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Content\Contracts\Services\ContentWorkflowServiceInterface;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Events\ContentApproved;
use Modules\Content\Events\ContentPublished;
use Modules\Content\Events\ContentRejected;
use Modules\Content\Events\ContentScheduled;
use Modules\Content\Events\ContentSubmitted;
use Modules\Content\Exceptions\InvalidTransitionException;
use Modules\Content\Models\ContentWorkflowHistory;

class ContentWorkflowService implements ContentWorkflowServiceInterface
{
    const STATE_DRAFT = 'draft';

    const STATE_SUBMITTED = 'submitted';

    const STATE_IN_REVIEW = 'in_review';

    const STATE_APPROVED = 'approved';

    const STATE_REJECTED = 'rejected';

    const STATE_SCHEDULED = 'scheduled';

    const STATE_PUBLISHED = 'published';

    const STATE_ARCHIVED = 'archived';

    protected array $transitions = [
        self::STATE_DRAFT => [self::STATE_SUBMITTED],
        self::STATE_SUBMITTED => [self::STATE_IN_REVIEW, self::STATE_DRAFT],
        self::STATE_IN_REVIEW => [self::STATE_APPROVED, self::STATE_REJECTED, self::STATE_DRAFT],
        self::STATE_APPROVED => [self::STATE_SCHEDULED, self::STATE_PUBLISHED, self::STATE_DRAFT],
        self::STATE_REJECTED => [self::STATE_DRAFT],
        self::STATE_SCHEDULED => [self::STATE_PUBLISHED, self::STATE_DRAFT],
        self::STATE_PUBLISHED => [self::STATE_ARCHIVED, self::STATE_DRAFT],
        self::STATE_ARCHIVED => [self::STATE_DRAFT],
    ];

    /**
     * @throws InvalidTransitionException
     */
    public function transition(Model $content, string $newState, User $user, ?string $note = null): bool
    {
        $currentStatus = $content->status instanceof ContentStatus
            ? $content->status->value
            : $content->status;

        if (! $this->canTransition($currentStatus, $newState)) {
            throw new InvalidTransitionException(
                "Cannot transition from {$currentStatus} to {$newState}"
            );
        }

        return DB::transaction(function () use ($content, $newState, $user, $note, $currentStatus) {
            ContentWorkflowHistory::create([
                'content_type' => get_class($content),
                'content_id' => $content->id,
                'from_state' => $currentStatus,
                'to_state' => $newState,
                'user_id' => $user->id,
                'note' => $note,
            ]);

            $content->update(['status' => $newState]);

            $this->fireTransitionEvent($content, $newState, $user);

            return true;
        });
    }

    public function canTransition(string|ContentStatus $currentState, string $newState): bool
    {
        $currentStateString = $currentState instanceof ContentStatus
            ? $currentState->value
            : $currentState;

        return collect($this->transitions[$currentStateString] ?? [])->contains($newState);
    }

    public function getAllowedTransitions(string $currentState): array
    {
        return $this->transitions[$currentState] ?? [];
    }

    /**
     * @throws InvalidTransitionException
     */
    public function submitForReview(Model $content, User $user): bool
    {
        return $this->transition($content, self::STATE_SUBMITTED, $user);
    }

    /**
     * @throws InvalidTransitionException
     */
    public function approve(Model $content, User $user, ?string $note = null): bool
    {
        return $this->transition($content, self::STATE_APPROVED, $user, $note);
    }

    /**
     * @throws InvalidTransitionException
     */
    public function reject(Model $content, User $user, string $reason): bool
    {
        return $this->transition($content, self::STATE_REJECTED, $user, $reason);
    }

    /**
     * @throws InvalidTransitionException
     */
    public function schedule(Model $content, User $user, \DateTime $publishDate): bool
    {
        $content->update(['scheduled_at' => $publishDate]);

        return $this->transition($content, self::STATE_SCHEDULED, $user);
    }

    /**
     * @throws InvalidTransitionException
     */
    public function publish(Model $content, User $user): bool
    {
        $content->update(['published_at' => now()]);

        return $this->transition($content, self::STATE_PUBLISHED, $user);
    }

    protected function fireTransitionEvent(Model $content, string $newState, User $user): void
    {
        switch ($newState) {
            case self::STATE_SUBMITTED:
                event(new ContentSubmitted($content, $user));
                break;
            case self::STATE_APPROVED:
                event(new ContentApproved($content, $user));
                break;
            case self::STATE_REJECTED:
                event(new ContentRejected($content, $user));
                break;
            case self::STATE_SCHEDULED:
                event(new ContentScheduled($content));
                break;
            case self::STATE_PUBLISHED:
                event(new ContentPublished($content));
                break;
        }
    }
}
