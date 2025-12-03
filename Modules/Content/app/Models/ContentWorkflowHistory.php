<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Auth\Models\User;

class ContentWorkflowHistory extends Model
{
    const UPDATED_AT = null;

    protected $table = 'content_workflow_history';

    protected $fillable = [
        'content_type',
        'content_id',
        'from_state',
        'to_state',
        'user_id',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the content that this history entry belongs to.
     */
    public function content(): MorphTo
    {
        return $this->morphTo('content', 'content_type', 'content_id');
    }

    /**
     * Get the user who performed the transition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
