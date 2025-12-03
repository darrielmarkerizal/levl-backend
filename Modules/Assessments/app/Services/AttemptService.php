<?php

namespace Modules\Assessments\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Modules\Assessments\Events\AttemptCompleted;
use Modules\Assessments\Events\QuestionAnswered;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Repositories\AttemptRepository;
use Modules\Auth\Models\User;

class AttemptService
{
  public function __construct(private readonly AttemptRepository $repository) {}

  public function paginate(User $user, array $params, int $perPage = 15): LengthAwarePaginator
  {
    return $this->repository->paginateForUser($user, $params, $perPage);
  }

  public function start(User $user, Exercise $exercise): Attempt
  {
    $enrollment = $this->repository->findEnrollmentForExercise($user, $exercise);
    if (!$enrollment) {
      $this->validationError([
        "exercise" => ["Anda belum terdaftar pada course yang memuat soal ini."],
      ]);
    }

    $now = Carbon::now();
    if ($exercise->available_from && $now->lt($exercise->available_from)) {
      $this->validationError([
        "exercise" => ["Soal ini belum tersedia."],
      ]);
    }

    if ($exercise->available_until && $now->gt($exercise->available_until)) {
      $this->validationError([
        "exercise" => ["Soal ini tidak lagi tersedia."],
      ]);
    }

    if ($exercise->status !== "published") {
      $this->validationError([
        "exercise" => ["Soal ini belum dipublikasikan."],
      ]);
    }

    // Check for active attempt (max 1 active attempt per user per exercise)
    $activeAttempt = $this->repository->findActiveAttempt($user, $exercise);
    if ($activeAttempt) {
      $this->validationError([
        "exercise" => ["Anda sudah memiliki attempt yang sedang berjalan untuk soal ini."],
      ]);
    }

    // Check retake permission if user has completed attempts
    $completedAttempts = $this->repository->countCompletedAttempts($user, $exercise);
    if ($completedAttempts > 0 && !$exercise->allow_retake) {
      $this->validationError([
        "exercise" => ["Soal ini tidak mengizinkan pengulangan."],
      ]);
    }

    $attempt = $this->repository->create([
      "exercise_id" => $exercise->id,
      "user_id" => $user->id,
      "enrollment_id" => $enrollment->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => $exercise->questions()->count(),
    ]);

    return $attempt->fresh();
  }

  public function show(Attempt $attempt): Attempt
  {
    // Check if attempt has expired due to time limit
    $this->checkAndExpireAttempt($attempt);

    return $this->repository->refreshWithDetails($attempt);
  }

  public function submitAnswer(Attempt $attempt, array $data)
  {
    // Check if attempt has expired
    $this->checkAndExpireAttempt($attempt);

    if ($attempt->status !== "in_progress") {
      $this->validationError([
        "attempt" => ["Upaya jawab ini sudah tidak lagi dalam proses."],
      ]);
    }

    $conditions = [
      "question_id" => $data["question_id"],
    ];
    $values = [
      "selected_option_id" => $data["selected_option_id"] ?? null,
      "answer_text" => $data["answer_text"] ?? null,
    ];

    $answer = $this->repository->firstOrCreateAnswer($attempt, $conditions, $values);
    if (!$answer->wasRecentlyCreated) {
      $answer = $this->repository->updateAnswer($answer, $values);
    }

    // Dispatch QuestionAnswered event
    QuestionAnswered::dispatch($answer->fresh());

    return $answer;
  }

  public function complete(Attempt $attempt): Attempt
  {
    // Check if attempt has expired
    $this->checkAndExpireAttempt($attempt);

    if ($attempt->status !== "in_progress") {
      $this->validationError([
        "attempt" => ["Upaya jawab ini sudah selesai."],
      ]);
    }

    $finishedAt = now();
    $durationSeconds = $attempt->started_at?->diffInSeconds($finishedAt);

    $attempt->update([
      "status" => "completed",
      "finished_at" => $finishedAt,
      "duration_seconds" => $durationSeconds,
    ]);

    $this->gradeAttempt($attempt);

    $freshAttempt = $attempt->fresh();

    // Dispatch event after grading is complete so score/correct_answers are populated
    AttemptCompleted::dispatch($freshAttempt);

    return $freshAttempt;
  }

  /**
   * Check if attempt has expired due to time limit and auto-complete if needed
   */
  private function checkAndExpireAttempt(Attempt $attempt): void
  {
    if ($attempt->status !== "in_progress") {
      return;
    }

    $exercise = $attempt->exercise;
    if (!$exercise->time_limit_minutes) {
      return;
    }

    $deadline = $attempt->started_at->addMinutes($exercise->time_limit_minutes);

    if (now()->gt($deadline)) {
      $attempt->update([
        "status" => "expired",
        "finished_at" => $deadline,
        "duration_seconds" => $exercise->time_limit_minutes * 60,
      ]);

      $this->gradeAttempt($attempt);
    }
  }

  private function gradeAttempt(Attempt $attempt): void
  {
    $totalScore = 0;
    $correctAnswers = 0;

    $answers = $this->repository->answersWithRelations($attempt);
    foreach ($answers as $answer) {
      $question = $answer->question;
      if (!$question) {
        continue;
      }

      if (in_array($question->type, ["multiple_choice", "true_false"], true)) {
        $score = 0;
        if ($answer->selectedOption && $answer->selectedOption->is_correct) {
          $score = $question->score_weight;
          $correctAnswers++;
        }

        $answer->update(["score_awarded" => $score]);
        $totalScore += $score;
      }
      // Essay/free_text remains pending manual grading (score_awarded = null)
    }

    $attempt->update([
      "score" => $totalScore,
      "correct_answers" => $correctAnswers,
    ]);
  }

  private function validationError(array $messages): void
  {
    throw ValidationException::withMessages($messages);
  }
}
