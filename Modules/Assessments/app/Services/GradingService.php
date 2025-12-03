<?php

namespace Modules\Assessments\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Assessments\Events\GradingCompleted;
use Modules\Assessments\Models\Answer;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Repositories\GradingRepository;

class GradingService
{
  public function __construct(private readonly GradingRepository $repository) {}

  public function attempts(
    Exercise $exercise,
    array $params,
    int $perPage = 15,
  ): LengthAwarePaginator {
    return $this->repository->paginateExerciseAttempts($exercise, $params, $perPage);
  }

  public function answers(Attempt $attempt): Collection
  {
    return $this->repository->answersForAttempt($attempt);
  }

  public function addFeedback(Answer $answer, array $data): Answer
  {
    $updatedAnswer = $this->repository->updateAnswer($answer, $data);

    // Recalculate attempt score after manual grading
    $this->recalculateAttemptScore($answer->attempt);

    return $updatedAnswer;
  }

  public function updateAttemptScore(Attempt $attempt, array $data): Attempt
  {
    $updatedAttempt = $this->repository->updateAttempt($attempt, $data);

    // Dispatch GradingCompleted event
    GradingCompleted::dispatch($updatedAttempt);

    return $updatedAttempt;
  }

  /**
   * Recalculate total score from all answers
   */
  private function recalculateAttemptScore(Attempt $attempt): void
  {
    $answers = $this->repository->answersForAttempt($attempt);

    $totalScore = $answers->sum("score_awarded") ?? 0;
    $correctAnswers = $answers
      ->filter(function ($answer) {
        return $answer->score_awarded > 0;
      })
      ->count();

    $this->repository->updateAttempt($attempt, [
      "score" => $totalScore,
      "correct_answers" => $correctAnswers,
    ]);
  }
}
