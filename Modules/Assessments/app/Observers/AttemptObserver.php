<?php

namespace Modules\Assessments\Observers;

use Modules\Assessments\Models\Attempt;

class AttemptObserver
{
  // AttemptCompleted event is now dispatched from AttemptService::complete()
// after grading is completed to ensure score/correct_answers are populated
}
