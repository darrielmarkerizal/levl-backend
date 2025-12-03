<?php

namespace Modules\Forums\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reply extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Maximum depth for nested replies.
     */
    const MAX_DEPTH = 5;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Forums\Database\Factories\ReplyFactory::new();
    }

    protected $fillable = [
        'thread_id',
        'parent_id',
        'author_id',
        'content',
        'depth',
        'is_accepted_answer',
        'edited_at',
        'deleted_by',
    ];

    protected $casts = [
        'depth' => 'integer',
        'is_accepted_answer' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically calculate depth when creating a reply
        static::creating(function ($reply) {
            if ($reply->parent_id) {
                $parent = static::find($reply->parent_id);
                $reply->depth = $parent ? $parent->depth + 1 : 0;
            } else {
                $reply->depth = 0;
            }
        });
    }

    /**
     * Get the thread that owns the reply.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Get the author of the reply.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'author_id');
    }

    /**
     * Get the parent reply.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Reply::class, 'parent_id');
    }

    /**
     * Get the child replies.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Reply::class, 'parent_id');
    }

    /**
     * Get the user who deleted the reply.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'deleted_by');
    }

    /**
     * Get all reactions for the reply.
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Check if the reply is marked as accepted answer.
     */
    public function isAcceptedAnswer(): bool
    {
        return $this->is_accepted_answer;
    }

    /**
     * Get the depth of the reply in the hierarchy.
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * Check if the reply can have children based on max depth.
     */
    public function canHaveChildren(): bool
    {
        return $this->depth < self::MAX_DEPTH;
    }

    /**
     * Scope a query to only include top-level replies (no parent).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include accepted answers.
     */
    public function scopeAccepted($query)
    {
        return $query->where('is_accepted_answer', true);
    }
}
