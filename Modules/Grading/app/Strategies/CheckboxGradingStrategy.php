<?php

declare(strict_types=1);

namespace Modules\Grading\Strategies;

use Modules\Grading\Contracts\GradingStrategyInterface;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;

class CheckboxGradingStrategy implements GradingStrategyInterface
{
    public function grade(Question $question, Answer $answer): ?float
    {
        $answerKey = $question->answer_key ?? [];
        $selectedOptions = $answer->selected_options ?? [];

        $correctSet = is_array($answerKey) ? $answerKey : [$answerKey];
        $selectedSet = is_array($selectedOptions) ? $selectedOptions : [$selectedOptions];

        $correctCount = 0;
        $wrongCount = 0;
        $totalCorrectOptions = count($correctSet);
        
        if ($totalCorrectOptions === 0) {
             return $selectedSet === $correctSet ? (float) $question->max_score : 0.0;
        }

        foreach ($selectedSet as $selection) {
            if (in_array($selection, $correctSet, true)) {
                $correctCount++;
            } else {
                $wrongCount++;
            }
        }

        $rawScore = ($correctCount - $wrongCount) / $totalCorrectOptions * $question->max_score;

        return max(0.0, (float) $rawScore);
    }

    public function canAutoGrade(): bool
    {
        return true;
    }
}
