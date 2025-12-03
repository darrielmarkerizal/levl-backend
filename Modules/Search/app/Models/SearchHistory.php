<?php

namespace Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class SearchHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'search_history';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'query',
        'filters',
        'results_count',
        'clicked_result_id',
        'clicked_result_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'results_count' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that performed the search.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
