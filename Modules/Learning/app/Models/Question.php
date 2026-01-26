<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Learning\Enums\QuestionType;

use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Question extends Model implements HasMedia
{
    use InteractsWithMedia, Searchable;
    protected $table = 'assignment_questions';
    protected $fillable = [
        'assignment_id',
        'type',
        'content',
        'options',
        'answer_key',
        'weight',
        'order',
        'max_score',
        'max_file_size',
        'allowed_file_types',
        'allow_multiple_files',
    ];

    protected $casts = [
        'type' => QuestionType::class,
        'options' => 'array',
        'answer_key' => 'array',
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'max_file_size' => 'integer',
        'allowed_file_types' => 'array',
        'allow_multiple_files' => 'boolean',
    ];

    protected static function newFactory()
    {
        return \Modules\Learning\Database\Factories\AssignmentQuestionFactory::new();
    }

        public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

        public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

        public function canAutoGrade(): bool
    {
        return $this->type->canAutoGrade();
    }

        public function requiresOptions(): bool
    {
        return $this->type->requiresOptions();
    }

        public function isValid(): bool
    {
        
        if (empty($this->content)) {
            return false;
        }

        
        if ($this->weight <= 0) {
            return false;
        }

        
        if ($this->requiresOptions() && empty($this->options)) {
            return false;
        }

        
        if ($this->canAutoGrade() && empty($this->answer_key)) {
            return false;
        }

        return true;
    }

        public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

        public function scopeOfType($query, QuestionType $type)
    {
        return $query->where('type', $type);
    }

        public function scopeAutoGradable($query)
    {
        return $query->whereIn('type', [
            QuestionType::MultipleChoice->value,
            QuestionType::Checkbox->value,
        ]);
    }

        public function scopeManualGrading($query)
    {
        return $query->whereIn('type', [
            QuestionType::Essay->value,
            QuestionType::FileUpload->value,
        ]);
    }

    public function searchableAs(): string
    {
        return 'questions_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'type' => $this->type->value,
            'content' => $this->content,
            'weight' => (float) $this->weight,
            'max_score' => (float) $this->max_score,
            'assignment_title' => $this->assignment?->title ?? '',
        ];
    }
}
