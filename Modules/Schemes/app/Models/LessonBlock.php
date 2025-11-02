<?php

namespace Modules\Schemes\Entities;

use Illuminate\Database\Eloquent\Model;

class LessonBlock extends Model
{
    protected $fillable = [
        'lesson_id', 'block_type', 'content', 'media_url', 'order'
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
