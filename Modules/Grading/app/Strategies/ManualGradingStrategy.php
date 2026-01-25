<?php

declare(strict_types=1);

namespace Modules\Grading\Strategies;

use Modules\Grading\Contracts\GradingStrategyInterface;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;

class ManualGradingStrategy implements GradingStrategyInterface
{
    public function grade(Question $question, Answer $answer): ?float
    {
        return null;
    }

    public function canAutoGrade(): bool
    {
        return false;
    }
}
