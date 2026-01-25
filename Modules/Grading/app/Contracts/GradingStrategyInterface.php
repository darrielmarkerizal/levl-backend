<?php

declare(strict_types=1);

namespace Modules\Grading\Contracts;

use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;

interface GradingStrategyInterface
{
    public function grade(Question $question, Answer $answer): ?float;

    public function canAutoGrade(): bool;
}
