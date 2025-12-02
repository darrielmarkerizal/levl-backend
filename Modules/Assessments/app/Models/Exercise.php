<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exercise extends Model
{
  use HasFactory;

  protected $fillable = [
    "scope_type",
    "scope_id",
    "created_by",
    "title",
    "description",
    "type",
    "time_limit_minutes",
    "max_score",
    "total_questions",
    "status",
    "allow_retake",
    "available_from",
    "available_until",
  ];

  protected $casts = [
    "available_from" => "datetime",
    "available_until" => "datetime",
    "allow_retake" => "boolean",
  ];

  /**
   * Calculate total points from questions
   */
  public function getTotalPointsAttribute(): int
  {
    return $this->questions()->sum("score_weight");
  }

  // Dynamic polymorphic relation: bisa ke Course/Unit/Lesson
  public function scope()
  {
    return match ($this->scope_type) {
      "course" => $this->belongsTo(\Modules\Schemes\Models\Course::class, "scope_id"),
      "unit" => $this->belongsTo(\Modules\Schemes\Models\Unit::class, "scope_id"),
      "lesson" => $this->belongsTo(\Modules\Schemes\Models\Lesson::class, "scope_id"),
    };
  }

  public function creator()
  {
    return $this->belongsTo(\Modules\Auth\Models\User::class, "created_by");
  }

  public function questions()
  {
    return $this->hasMany(Question::class);
  }

  public function attempts()
  {
    return $this->hasMany(Attempt::class);
  }

  protected static function newFactory()
  {
    return \Modules\Assessments\Database\Factories\ExerciseFactory::new();
  }
}
