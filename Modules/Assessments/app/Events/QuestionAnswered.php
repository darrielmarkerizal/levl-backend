<?php

namespace Modules\Assessments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Assessments\Models\Answer;

class QuestionAnswered
{
  use Dispatchable, SerializesModels;

  public function __construct(public Answer $answer) {}
}
