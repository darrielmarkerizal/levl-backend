<?php

use Illuminate\Support\Facades\Event;
use Modules\Assessments\Events\GradingCompleted;
use Modules\Assessments\Models\Answer;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
  createTestRoles();

  $this->instructor = User::factory()->create();
  $this->instructor->assignRole("Instructor");

  $this->student = User::factory()->create();
  $this->student->assignRole("Student");

  $this->course = Course::factory()->create(["instructor_id" => $this->instructor->id]);
  Enrollment::create([
    "user_id" => $this->student->id,
    "course_id" => $this->course->id,
    "status" => "active",
  ]);

  $this->exercise = Exercise::factory()->create([
    "created_by" => $this->instructor->id,
    "scope_type" => "course",
    "scope_id" => $this->course->id,
    "status" => "published",
    "max_score" => 100,
  ]);

  // Add questions
  $this->mcQuestion = $this->exercise->questions()->create([
    "question_text" => "MC Question?",
    "type" => "multiple_choice",
    "score_weight" => 10,
  ]);
  $this->correctOption = $this->mcQuestion->options()->create([
    "option_text" => "Correct",
    "is_correct" => true,
  ]);
  $this->wrongOption = $this->mcQuestion->options()->create([
    "option_text" => "Wrong",
    "is_correct" => false,
  ]);

  $this->essayQuestion = $this->exercise->questions()->create([
    "question_text" => "Essay Question?",
    "type" => "free_text",
    "score_weight" => 20,
  ]);
});

describe("Auto-Grading Accuracy Tests", function () {
  it("correctly grades multiple choice with correct answer", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 2,
    ]);

    // Submit correct answer
    $attempt->answers()->create([
      "question_id" => $this->mcQuestion->id,
      "selected_option_id" => $this->correctOption->id,
    ]);

    // Complete attempt
    $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $attempt->refresh();
    $answer = $attempt->answers()->where("question_id", $this->mcQuestion->id)->first();

    expect($answer->score_awarded)->toBe(10);
    expect($attempt->score)->toBe(10);
    expect($attempt->correct_answers)->toBe(1);
  });

  it("correctly grades multiple choice with wrong answer", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 2,
    ]);

    // Submit wrong answer
    $attempt->answers()->create([
      "question_id" => $this->mcQuestion->id,
      "selected_option_id" => $this->wrongOption->id,
    ]);

    $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $attempt->refresh();
    $answer = $attempt->answers()->where("question_id", $this->mcQuestion->id)->first();

    expect($answer->score_awarded)->toBe(0);
    expect($attempt->score)->toBe(0);
    expect($attempt->correct_answers)->toBe(0);
  });

  it("correctly grades true/false questions", function () {
    $tfQuestion = $this->exercise->questions()->create([
      "question_text" => "True or False?",
      "type" => "true_false",
      "score_weight" => 5,
    ]);
    $trueOption = $tfQuestion->options()->create([
      "option_text" => "True",
      "is_correct" => true,
    ]);
    $falseOption = $tfQuestion->options()->create([
      "option_text" => "False",
      "is_correct" => false,
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 3,
    ]);

    // Submit correct true/false answer
    $attempt->answers()->create([
      "question_id" => $tfQuestion->id,
      "selected_option_id" => $trueOption->id,
    ]);

    $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $attempt->refresh();
    $answer = $attempt->answers()->where("question_id", $tfQuestion->id)->first();

    expect($answer->score_awarded)->toBe(5);
  });

  it("leaves essay questions pending for manual grading", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 2,
    ]);

    // Submit essay answer
    $attempt->answers()->create([
      "question_id" => $this->essayQuestion->id,
      "answer_text" => "This is my detailed essay answer.",
    ]);

    $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $attempt->refresh();
    $answer = $attempt->answers()->where("question_id", $this->essayQuestion->id)->first();

    // Essay should have null score (pending manual grading)
    expect($answer->score_awarded)->toBeNull();
  });
});

describe("Manual Grading Tests", function () {
  it("instructor can get exercise attempts", function () {
    Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $response = $this->actingAs($this->instructor, "api")->getJson(
      api("/assessments/exercises/{$this->exercise->id}/attempts"),
    );

    $response->assertStatus(200);
    expect(count($response->json("data")))->toBe(1);
  });

  it("instructor can get attempt answers", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $attempt->answers()->create([
      "question_id" => $this->essayQuestion->id,
      "answer_text" => "Student essay answer.",
    ]);

    $response = $this->actingAs($this->instructor, "api")->getJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
    );

    $response->assertStatus(200);
    expect(count($response->json("data.answers")))->toBe(1);
  });

  it("instructor can add feedback to essay answer", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
      "score" => 0,
    ]);

    $answer = $attempt->answers()->create([
      "question_id" => $this->essayQuestion->id,
      "answer_text" => "Student essay answer.",
      "score_awarded" => null,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/answers/{$answer->id}/feedback"),
      [
        "feedback" => "Good analysis but needs more detail.",
        "score_awarded" => 15,
      ],
    );

    $response
      ->assertStatus(200)
      ->assertJsonPath("data.answer.feedback", "Good analysis but needs more detail.")
      ->assertJsonPath("data.answer.score_awarded", 15);

    // Verify attempt score was recalculated
    $attempt->refresh();
    expect($attempt->score)->toBe(15);
  });

  it("instructor can update attempt final score", function () {
    Event::fake([GradingCompleted::class]);

    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
      "score" => 15,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/score"),
      [
        "score" => 85,
        "feedback" => "Overall good performance.",
      ],
    );

    $response
      ->assertStatus(200)
      ->assertJsonPath("data.attempt.score", 85)
      ->assertJsonPath("data.attempt.feedback", "Overall good performance.");

    Event::assertDispatched(GradingCompleted::class);
  });

  it("student cannot grade answers", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $answer = $attempt->answers()->create([
      "question_id" => $this->essayQuestion->id,
      "answer_text" => "Essay answer.",
    ]);

    $response = $this->actingAs($this->student, "api")->putJson(
      api("/assessments/answers/{$answer->id}/feedback"),
      [
        "feedback" => "Trying to grade myself.",
        "score_awarded" => 100,
      ],
    );

    $response->assertStatus(403);
  });

  it("student cannot update attempt score", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/score"),
      [
        "score" => 100,
      ],
    );

    $response->assertStatus(403);
  });
});

describe("Score Calculation Tests", function () {
  it("calculates score correctly for mixed question types", function () {
    // Add true/false question
    $tfQuestion = $this->exercise->questions()->create([
      "question_text" => "True or False?",
      "type" => "true_false",
      "score_weight" => 5,
    ]);
    $trueOption = $tfQuestion->options()->create([
      "option_text" => "True",
      "is_correct" => true,
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 3,
    ]);

    // Submit MC correct (10 points)
    $attempt->answers()->create([
      "question_id" => $this->mcQuestion->id,
      "selected_option_id" => $this->correctOption->id,
    ]);

    // Submit TF correct (5 points)
    $attempt->answers()->create([
      "question_id" => $tfQuestion->id,
      "selected_option_id" => $trueOption->id,
    ]);

    // Submit essay (pending)
    $attempt->answers()->create([
      "question_id" => $this->essayQuestion->id,
      "answer_text" => "Essay answer.",
    ]);

    $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $attempt->refresh();

    // Auto-graded score: 10 + 5 = 15
    expect($attempt->score)->toBe(15);
    expect($attempt->correct_answers)->toBe(2);

    // Now instructor grades essay (20 points)
    $essayAnswer = $attempt->answers()->where("question_id", $this->essayQuestion->id)->first();

    $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/answers/{$essayAnswer->id}/feedback"),
      [
        "feedback" => "Excellent work!",
        "score_awarded" => 18,
      ],
    );

    $attempt->refresh();
    // Total: 10 + 5 + 18 = 33
    expect($attempt->score)->toBe(33);
  });

  it("recalculates score after each manual grading", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
      "score" => 0,
    ]);

    $answer = $attempt->answers()->create([
      "question_id" => $this->essayQuestion->id,
      "answer_text" => "Essay answer.",
    ]);

    // First grading
    $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/answers/{$answer->id}/feedback"),
      [
        "feedback" => "Needs improvement.",
        "score_awarded" => 10,
      ],
    );

    $attempt->refresh();
    expect($attempt->score)->toBe(10);

    // Re-grade with different score
    $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/answers/{$answer->id}/feedback"),
      [
        "feedback" => "Better after review.",
        "score_awarded" => 15,
      ],
    );

    $attempt->refresh();
    expect($attempt->score)->toBe(15);
  });
});
