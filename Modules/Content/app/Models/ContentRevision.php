<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class ContentRevision extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static function newFactory()
    {
        return \Modules\Content\Database\Factories\ContentRevisionFactory::new();
    }

    protected $fillable = [
        'content_type',
        'content_id',
        'editor_id',
        'title',
        'content',
        'revision_note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($revision) {
            $revision->created_at = now();
        });
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function content()
    {
        return $this->morphTo('content', 'content_type', 'content_id');
    }
}
