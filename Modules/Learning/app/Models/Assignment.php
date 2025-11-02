<?php

namespace Modules\Learning\Entities;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = [
        'lesson_id', 'created_by', 'title', 'description',
        'type', 'submission_type', 'max_score',
        'available_from', 'deadline_at', 'status'
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    public function lesson()
    {
        return $this->belongsTo(\Modules\Schemes\Entities\Lesson::class);
    }

    public function creator()
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
