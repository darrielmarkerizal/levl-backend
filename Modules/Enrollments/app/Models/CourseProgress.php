<?php

declare(strict_types=1);

namespace Modules\Enrollments\Models;

use Database\Factories\CourseProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Enrollments\Enums\ProgressStatus;

class CourseProgress extends Model
{
    use HasFactory;

    protected $table = 'course_progress';

    protected $fillable = [
        'enrollment_id',
        'status',
        'progress_percent',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => ProgressStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percent' => 'float',
    ];

    protected static function newFactory()
    {
        return CourseProgressFactory::new();
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function course()
    {
        return $this->hasOneThrough(
            \Modules\Schemes\Models\Course::class,
            Enrollment::class,
            'id',
            'id',
            'enrollment_id',
            'course_id',
        );
    }

    public function getCourseIdAttribute()
    {
        return $this->enrollment?->course_id;
    }
}
