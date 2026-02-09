<?php

declare(strict_types=1);

namespace Modules\Forums\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Thread extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Searchable, InteractsWithMedia;

    protected static function newFactory()
    {
        return \Modules\Forums\Database\Factories\ThreadFactory::new();
    }

    protected $fillable = [
        'course_id',
        'author_id',
        'title',
        'content',
        'is_pinned',
        'is_closed',
        'is_resolved',
        'views_count',
        'replies_count',
        'last_activity_at',
        'edited_at',
        'deleted_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_closed' => 'boolean',
        'is_resolved' => 'boolean',
        'views_count' => 'integer',
        'replies_count' => 'integer',
        'last_activity_at' => 'datetime',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'author_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'deleted_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    public function topLevelReplies(): HasMany
    {
        return $this->hasMany(Reply::class)->whereNull('parent_id');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeForCourse($query, int $courseId)
    {
        return $this->scopeByCourse($query, $courseId);
    }

    public function scopeForScheme($query, int $schemeId)
    {
        return $this->scopeForCourse($query, $schemeId);
    }

    public function scopePinned($query, bool $isPinned = true)
    {
        return $query->where('is_pinned', $isPinned);
    }

    public function scopeResolved($query, bool $isResolved = true)
    {
        return $query->where('is_resolved', $isResolved);
    }

    public function scopeClosed($query, bool $isClosed = true)
    {
        return $query->where('is_closed', $isClosed);
    }

    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    public function isClosed(): bool
    {
        return $this->is_closed;
    }

    public function isResolved(): bool
    {
        return $this->is_resolved;
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function scopeWithIsMentioned($query)
    {
        if (auth()->check()) {
            return $query->withExists(['mentions as is_mentioned' => function ($q) {
                $q->where('user_id', auth()->id());
            }]);
        }
        return $query;
    }

    public function scopeIsMentioned($query, $state = true)
    {
        if (!auth()->check()) {
            return $query;
        }

        $method = filter_var($state, FILTER_VALIDATE_BOOLEAN) ? 'whereHas' : 'whereDoesntHave';

        return $query->$method('mentions', function ($q) {
            $q->where('user_id', auth()->id());
        });
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'course_id' => $this->course_id,
            'author_id' => $this->author_id,
            'author_name' => $this->author->name ?? '',
        ];
    }

    public function searchableAs(): string
    {
        return 'threads_index';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('do');
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->sharpen(10)
            ->performOnCollections('attachments');

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->performOnCollections('attachments');
    }
}
