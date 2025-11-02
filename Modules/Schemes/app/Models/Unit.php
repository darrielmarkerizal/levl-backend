<?php

namespace Modules\Schemes\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Schemes\Entities\Lesson;

class Unit extends Model
{
    protected $fillable = [
        'course_id', 'code', 'slug', 'title', 'description',
        'order', 'estimated_duration'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}
