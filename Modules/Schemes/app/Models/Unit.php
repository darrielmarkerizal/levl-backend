<?php

declare(strict_types=1);

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Unit extends Model
{
    use HasFactory, HasSlug, LogsActivity, Searchable;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Unit baru telah dibuat',
                'updated' => 'Unit telah diperbarui',
                'deleted' => 'Unit telah dihapus',
                default => "Unit {$eventName}",
            });
    }

    protected $fillable = [
        'course_id', 'code', 'slug', 'title', 'description',
        'order', 'status',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(\Modules\Schemes\Models\Lesson::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing('course');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'code' => $this->code,
            'course_id' => $this->course_id,
            'course_title' => $this->course?->title,
            'status' => $this->status,
        ];
    }

    public function searchableAs(): string
    {
        return 'units_index';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }

    protected static function newFactory()
    {
        return \Modules\Schemes\Database\Factories\UnitFactory::new();
    }
}
