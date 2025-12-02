<?php

namespace Modules\Assessments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Assessments\Models\Attempt;

class GradingCompleted
{
  use Dispatchable, SerializesModels;

  public function __construct(public Attempt $attempt) {}
}
