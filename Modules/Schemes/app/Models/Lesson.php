<?php

declare(strict_types=1);

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Modules\Schemes\Enums\ContentType;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Lesson extends Model
{
    use HasFactory, HasSlug, LogsActivity, Searchable;

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * Get activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Lesson baru telah dibuat',
                'updated' => 'Lesson telah diperbarui',
                'deleted' => 'Lesson telah dihapus',
                default => "Lesson {$eventName}",
            });
    }

    protected $fillable = [
        'unit_id', 'slug', 'title', 'description',
        'markdown_content', 'content_type', 'content_url',
        'order', 'duration_minutes', 'status', 'published_at',
    ];

    protected $casts = [
        'order' => 'integer',
        'duration_minutes' => 'integer',
        'published_at' => 'datetime',
        'content_type' => ContentType::class,
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function blocks()
    {
        return $this->hasMany(LessonBlock::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['unit', 'unit.course']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'markdown_content' => $this->markdown_content,
            'unit_id' => $this->unit_id,
            'unit_title' => $this->unit?->title,
            'course_id' => $this->unit?->course_id,
            'course_title' => $this->unit?->course?->title,
            'status' => $this->status,
            'content_type' => $this->content_type?->value,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'lessons_index';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Schemes\Database\Factories\LessonFactory::new();
    }
}
