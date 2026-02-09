<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Answer extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'submission_id',
        'question_id',
        'content',
        'selected_options',
        'file_paths',
        'score',
        'is_auto_graded',
        'feedback',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'file_paths' => 'array',
        'score' => 'decimal:2',
        'is_auto_graded' => 'boolean',
    ];

    protected static function newFactory()
    {
        return \Modules\Learning\Database\Factories\AnswerFactory::new();
    }

        public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

        public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

        public function isGraded(): bool
    {
        return $this->score !== null;
    }

        public function needsManualGrading(): bool
    {
        return ! $this->isGraded() && ! $this->question->canAutoGrade();
    }

        public function getAnswerValueAttribute(): mixed
    {
        $question = $this->question;

        if (! $question) {
            return null;
        }

        return match (true) {
            $question->type->requiresOptions() => $this->selected_options,
            $question->type === \Modules\Learning\Enums\QuestionType::FileUpload => $this->file_paths,
            default => $this->content,
        };
    }

        public function hasRichTextFeedback(): bool
    {
        if (empty($this->feedback)) {
            return false;
        }

        
        return $this->feedback !== strip_tags($this->feedback);
    }

        public function getPlainTextFeedbackAttribute(): ?string
    {
        if (empty($this->feedback)) {
            return null;
        }

        return strip_tags($this->feedback);
    }

        public function scopeGraded($query, bool $isGraded = true)
    {
        if ($isGraded) {
            return $query->whereNotNull('score');
        }
        return $query->whereNull('score');
    }

        public function scopeUngraded($query, bool $isUngraded = true)
    {
        if ($isUngraded) {
            return $query->whereNull('score');
        }
        return $query->whereNotNull('score');
    }

        public function scopeAutoGraded($query, bool $isAutoGraded = true)
    {
        return $query->where('is_auto_graded', $isAutoGraded);
    }

        public function scopeManuallyGraded($query, bool $isManuallyGraded = true)
    {
        if ($isManuallyGraded) {
            return $query->where('is_auto_graded', false)->whereNotNull('score');
        }
        return $query->where(function ($q) {
            $q->where('is_auto_graded', true)->orWhereNull('score');
        });
    }

        public function scopeWithFeedback($query, bool $hasFeedback = true)
    {
        if ($hasFeedback) {
            return $query->whereNotNull('feedback')->where('feedback', '!=', '');
        }
        return $query->where(function ($q) {
            $q->whereNull('feedback')->orWhere('feedback', '');
        });
    }

        public function scopeWithFiles(Builder $query): Builder
    {
        return $query->whereNotNull('file_paths')
            ->whereRaw('JSON_LENGTH(file_paths) > 0');
    }

        public function hasFiles(): bool
    {
        return ! empty($this->file_paths) && is_array($this->file_paths) && count($this->file_paths) > 0;
    }
}
