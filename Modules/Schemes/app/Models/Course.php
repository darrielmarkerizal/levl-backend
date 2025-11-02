<?php

namespace Modules\Schemes\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Schemes\Entities\CourseTag;
use Modules\Schemes\Entities\Unit;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'slug', 'title', 'short_desc', 'type',
        'level_tag', 'category', 'tags_json', 'outcomes_json',
        'prereq_text', 'duration_estimate', 'thumbnail_path',
        'banner_path', 'visibility', 'progression_mode',
        'status', 'published_at'
    ];

    protected $casts = [
        'tags_json' => 'array',
        'outcomes_json' => 'array',
        'published_at' => 'datetime',
    ];

    public function tags()
    {
        return $this->hasMany(CourseTag::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
