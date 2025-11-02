<?php

namespace Modules\Schemes\Entities;

use Illuminate\Database\Eloquent\Model;

class CourseTag extends Model
{
    protected $fillable = ['course_id', 'tag'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
