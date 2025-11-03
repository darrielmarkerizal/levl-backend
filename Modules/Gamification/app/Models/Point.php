<?php

namespace Modules\Gamification\Entities;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    protected $fillable = [
        'user_id', 'source_type', 'source_id', 'points', 'reason', 'description'
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class);
    }
}
