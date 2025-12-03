<?php

namespace Modules\Forums\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Schemes\Models\Course;

class Thread extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Forums\Database\Factories\ThreadFactory::new();
    }

    protected $fillable = [
        'scheme_id',
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

    /**
     * Get the scheme (course) that owns the thread.
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'scheme_id');
    }

    /**
     * Get the author of the thread.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'author_id');
    }

    /**
     * Get the user who deleted the thread.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'deleted_by');
    }

    /**
     * Get all replies for the thread.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    /**
     * Get all reactions for the thread.
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Scope a query to only include threads for a specific scheme.
     */
    public function scopeForScheme($query, int $schemeId)
    {
        return $query->where('scheme_id', $schemeId);
    }

    /**
     * Scope a query to only include pinned threads.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope a query to only include resolved threads.
     */
    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope a query to only include closed threads.
     */
    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    /**
     * Scope a query to only include open threads.
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Check if the thread is pinned.
     */
    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    /**
     * Check if the thread is closed.
     */
    public function isClosed(): bool
    {
        return $this->is_closed;
    }

    /**
     * Check if the thread is resolved.
     */
    public function isResolved(): bool
    {
        return $this->is_resolved;
    }

    /**
     * Increment the views count for the thread.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Update the last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
