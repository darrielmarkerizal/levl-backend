<?php

declare(strict_types=1);

namespace Modules\Grading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Grading\Enums\AppealStatus;

class Appeal extends Model
{
    protected $fillable = [
        'submission_id',
        'student_id',
        'reviewer_id',
        'reason',
        'supporting_documents',
        'status',
        'decision_reason',
        'submitted_at',
        'decided_at',
    ];

    protected $casts = [
        'reason' => 'string',
        'supporting_documents' => 'array',
        'status' => AppealStatus::class,
        'decision_reason' => 'string',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(\Modules\Learning\Models\Submission::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'reviewer_id');
    }

    public function approve(int $reviewerId): bool
    {
        if ($this->status->isDecided()) {
            throw new \InvalidArgumentException('Appeal has already been decided');
        }

        $this->status = AppealStatus::Approved;
        $this->reviewer_id = $reviewerId;
        $this->decided_at = now();

        return $this->save();
    }

    public function deny(int $reviewerId, string $reason): bool
    {
        if ($this->status->isDecided()) {
            throw new \InvalidArgumentException('Appeal has already been decided');
        }

        if (empty($reason)) {
            throw new \InvalidArgumentException('Decision reason is required when denying an appeal');
        }

        $this->status = AppealStatus::Denied;
        $this->reviewer_id = $reviewerId;
        $this->decision_reason = $reason;
        $this->decided_at = now();

        return $this->save();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isDecided(): bool
    {
        return $this->status->isDecided();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isDenied(): bool
    {
        return $this->status->isDenied();
    }

    public function scopePending($query)
    {
        return $query->where('status', AppealStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AppealStatus::Approved);
    }

    public function scopeDenied($query)
    {
        return $query->where('status', AppealStatus::Denied);
    }

    public function scopeForSubmission($query, int $submissionId)
    {
        return $query->where('submission_id', $submissionId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeReviewedBy($query, int $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }
}
