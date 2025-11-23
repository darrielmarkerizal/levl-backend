<?php

namespace Modules\Assessments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Answer;
use Modules\Assessments\Services\GradingService;

class GradingController extends Controller
{
  use ApiResponse;

  public function __construct(private readonly GradingService $service) {}

  /**
   * Get all attempts for an exercise
   */
  public function getExerciseAttempts(Request $request, Exercise $exercise)
  {
    $this->authorize("view", $exercise);

    $result = $this->service->attempts(
      $exercise,
      $request->all(),
      (int) $request->get("per_page", 15),
    );

    if ($result instanceof LengthAwarePaginator) {
      return $this->paginateResponse($result, "Daftar attempt berhasil diambil");
    }

    return $this->success(["attempts" => $result], "Daftar attempt berhasil diambil");
  }

  /**
   * Get all answers for an attempt
   */
  public function getAttemptAnswers(Attempt $attempt)
  {
    $this->authorize("view", $attempt);

    $answers = $this->service->answers($attempt);

    return $this->success(["answers" => $answers], "Daftar jawaban berhasil diambil");
  }

  /**
   * Add feedback to an answer (for essay/short answer)
   */
  public function addFeedback(Request $request, Answer $answer)
  {
    $this->authorize("view", $answer->attempt);

    $validated = $request->validate([
      "feedback" => "required|string",
      "score_awarded" => "required|numeric|min:0",
    ]);

    $answer = $this->service->addFeedback($answer, $validated);

    return $this->success(["answer" => $answer], "Feedback berhasil ditambahkan");
  }

  /**
   * Update attempt's final score and feedback
   */
  public function updateAttemptScore(Request $request, Attempt $attempt)
  {
    $this->authorize("view", $attempt);

    $validated = $request->validate([
      "score" => "required|numeric|min:0",
      "feedback" => "nullable|string",
    ]);

    $attempt = $this->service->updateAttemptScore($attempt, $validated);

    return $this->success(["attempt" => $attempt], "Score attempt berhasil diperbarui");
  }
}
