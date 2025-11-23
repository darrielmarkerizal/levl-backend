<?php

namespace Modules\Assessments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Services\AttemptService;

class AttemptController extends Controller
{
  use ApiResponse;

  public function __construct(private readonly AttemptService $service) {}

  /**
   * List user's attempts
   */
  public function index(Request $request)
  {
    $user = $request->user();

    $attempts = $this->service->paginate(
      $user,
      $request->all(),
      (int) $request->get("per_page", 15),
    );

    if ($attempts instanceof LengthAwarePaginator) {
      return $this->paginateResponse($attempts, "Daftar attempt berhasil diambil");
    }

    return $this->success(["attempts" => $attempts], "Daftar attempt berhasil diambil");
  }

  /**
   * Start new attempt
   */
  public function store(Request $request, Exercise $exercise)
  {
    $user = $request->user();

    $attempt = $this->service->start($user, $exercise);

    return $this->created(["attempt" => $attempt], "Attempt berhasil dimulai");
  }

  /**
   * Get attempt details with questions
   */
  public function show(Attempt $attempt)
  {
    $this->authorize("view", $attempt);

    $attempt = $this->service->show($attempt);

    return $this->success(["attempt" => $attempt], "Detail attempt berhasil diambil");
  }

  /**
   * Submit answer for a question
   */
  public function submitAnswer(Request $request, Attempt $attempt)
  {
    $this->authorize("view", $attempt);

    $validated = $request->validate([
      "question_id" => "required|integer|exists:questions,id",
      "selected_option_id" => "nullable|integer|exists:question_options,id",
      "answer_text" => "nullable|string",
    ]);

    $answer = $this->service->submitAnswer($attempt, $validated);

    return $this->success(["answer" => $answer], "Jawaban berhasil disimpan");
  }

  /**
   * Complete attempt and trigger grading
   */
  public function complete(Request $request, Attempt $attempt)
  {
    $this->authorize("view", $attempt);

    $attempt = $this->service->complete($attempt);

    return $this->success(["attempt" => $attempt], "Attempt berhasil diselesaikan");
  }
}
