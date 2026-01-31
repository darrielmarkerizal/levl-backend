<?php

declare(strict_types=1);

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\LevelTag;
use Modules\Schemes\Enums\ProgressionMode;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Course extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(225)
            ->sharpen(10)
            ->performOnCollections('thumbnail', 'banner');

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(450)
            ->performOnCollections('thumbnail', 'banner');

        $this->addMediaConversion('large')->width(1920)->height(1080)->performOnCollections('banner');

        $this->addMediaConversion('mobile')->width(320)->height(180)->performOnCollections('thumbnail');

        $this->addMediaConversion('tablet')->width(600)->height(338)->performOnCollections('thumbnail');
    }

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
            ->setDescriptionForEvent(
                fn (string $eventName) => match ($eventName) {
                    'created' => 'Course baru telah dibuat',
                    'updated' => 'Course telah diperbarui',
                    'deleted' => 'Course telah dihapus',
                    default => "Course {$eventName}",
                },
            );
    }

    protected array $searchable = ['title', 'short_desc'];

    protected $fillable = [
        'code',
        'slug',
        'title',
        'short_desc',
        'type',
        'level_tag',
        'category_id',
        'tags_json',
        'prereq_text',
        'duration_estimate',
        'progression_mode',
        'enrollment_type',
        'enrollment_key',
        'enrollment_key_hash',
        'status',
        'published_at',
        'instructor_id',
    ];

    protected $guarded = [
        'deleted_by',
    ];

    protected $casts = [
        'tags_json' => 'array',
        'published_at' => 'datetime',
        'status' => CourseStatus::class,
        'type' => CourseType::class,
        'level_tag' => LevelTag::class,
        'enrollment_type' => EnrollmentType::class,
        'progression_mode' => ProgressionMode::class,
    ];

    protected $appends = ['tag_list'];

    protected $hidden = ['enrollment_key_hash', 'deleted_at'];

    public function getThumbnailUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('thumbnail');

        return $media?->getUrl();
    }

    public function getThumbnailThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('thumbnail');

        return $media?->getUrl('thumb');
    }

    public function getBannerUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('banner');

        return $media?->getUrl();
    }

    public function getBannerLargeUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('banner');

        return $media?->getUrl('large');
    }

    public function getThumbnailUrlEfficient(): string
    {
        if ($this->relationLoaded('media')) {
            $thumbnailMedia = $this->media->where('collection_name', 'thumbnail')->first();

            return $thumbnailMedia ? $thumbnailMedia->getUrl() : '';
        }

        return $this->getThumbnailUrlAttribute() ?? '';
    }

    public function getBannerUrlEfficient(): string
    {
        if ($this->relationLoaded('media')) {
            $bannerMedia = $this->media->where('collection_name', 'banner')->first();

            return $bannerMedia ? $bannerMedia->getUrl() : '';
        }

        return $this->getBannerUrlAttribute() ?? '';
    }

    public function scopeWithMediaUrls($query)
    {
        return $query->addSelect([
            'thumbnail_url' => \Spatie\MediaLibrary\MediaCollections\Models\Media::selectRaw('CONCAT("/storage/", directory, "/", file_name)')
                ->whereColumn('model_id', 'courses.id')
                ->where('model_type', static::class)
                ->where('collection_name', 'thumbnail')
                ->limit(1),
            'banner_url' => \Spatie\MediaLibrary\MediaCollections\Models\Media::selectRaw('CONCAT("/storage/", directory, "/", file_name)')
                ->whereColumn('model_id', 'courses.id')
                ->where('model_type', static::class)
                ->where('collection_name', 'banner')
                ->limit(1),
        ]);
    }

    public function tagPivot(): HasMany
    {
        return $this->hasMany(CourseTag::class, 'course_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'course_tag_pivot',
            'course_id',
            'tag_id',
        )->withTimestamps();
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'instructor_id');
    }

    public function getCreatorAttribute()
    {
        if ($this->relationLoaded('admins')) {
            return $this->admins->sortBy('pivot.created_at')->first();
        }

        return $this->admins()->orderBy('course_admins.created_at', 'asc')->first();
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'deleted_by');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Auth\Models\User::class,
            'course_admins',
            'course_id',
            'user_id',
        )->withTimestamps();
    }

    public function courseAdmins(): HasMany
    {
        return $this->hasMany(\Modules\Schemes\Models\CourseAdmin::class);
    }

    public function hasAdmin($user): bool
    {
        return $this->admins()
            ->where('user_id', is_object($user) ? $user->id : $user)
            ->exists();
    }

    public function hasInstructor($user): bool
    {
        return $this->instructor_id === (is_object($user) ? $user->id : $user);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getTagListAttribute(): array
    {
        if ($this->relationLoaded('tags')) {
            return $this->tags->pluck('name')->unique()->values()->toArray();
        }

        if (is_array($this->tags_json)) {
            return $this->tags_json;
        }

        return [];
    }

    public function getEnrollmentKeyAttribute(): ?string
    {
        return null;
    }

    public function setEnrollmentKeyAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['enrollment_key_hash'] = null;
        } else {
            $hasher = app(\App\Contracts\EnrollmentKeyHasherInterface::class);
            $this->attributes['enrollment_key_hash'] = $hasher->hash($value);
        }
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\Modules\Common\Models\Category::class, 'category_id');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(CourseOutcome::class)->orderBy('order');
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['category', 'instructor', 'tags']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'short_desc' => $this->short_desc,
            'code' => $this->code,
            'level_tag' => $this->level_tag?->value,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'instructor_id' => $this->instructor_id,
            'instructor_name' => $this->instructor?->name,
            'tags' => $this->tags->pluck('name')->toArray(),
            'status' => $this->status?->value,
            'type' => $this->type?->value,
            'duration_estimate' => $this->duration_estimate,
            'published_at' => $this->published_at?->timestamp,
        ];
    }

    public function searchableAs(): string
    {
        return 'courses_index';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    protected static function newFactory()
    {
        return \Modules\Schemes\Database\Factories\CourseFactory::new();
    }
}
