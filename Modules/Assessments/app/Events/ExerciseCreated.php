<?php

namespace Modules\Assessments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Assessments\Models\Exercise;

class ExerciseCreated
{
  use Dispatchable, SerializesModels;

  public function __construct(public Exercise $exercise) {}
}
