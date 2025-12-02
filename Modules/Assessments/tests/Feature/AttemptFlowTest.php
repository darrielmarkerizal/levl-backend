<?php

use Illuminate\Support\Facades\Event;
use Modules\Assessments\Events\AttemptCompleted;
use Modules\Assessments\Events\QuestionAnswered;
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

  // Published exercise with questions
  $this->exercise = Exercise::factory()->create([
    "created_by" => $this->instructor->id,
    "scope_type" => "course",
    "scope_id" => $this->course->id,
    "status" => "published",
    "allow_retake" => true,
    "time_limit_minutes" => 60,
    "available_from" => now()->subDay(),
    "available_until" => now()->addDay(),
  ]);

  // Add questions with options
  $this->mcQuestion = $this->exercise->questions()->create([
    "question_text" => "What is 2+2?",
    "type" => "multiple_choice",
    "score_weight" => 10,
    "order" => 1,
  ]);
  $this->correctOption = $this->mcQuestion->options()->create([
    "option_text" => "4",
    "is_correct" => true,
    "order" => 1,
  ]);
  $this->wrongOption = $this->mcQuestion->options()->create([
    "option_text" => "5",
    "is_correct" => false,
    "order" => 2,
  ]);

  $this->tfQuestion = $this->exercise->questions()->create([
    "question_text" => "The sky is blue.",
    "type" => "true_false",
    "score_weight" => 5,
    "order" => 2,
  ]);
  $this->trueOption = $this->tfQuestion->options()->create([
    "option_text" => "True",
    "is_correct" => true,
    "order" => 1,
  ]);
  $this->falseOption = $this->tfQuestion->options()->create([
    "option_text" => "False",
    "is_correct" => false,
    "order" => 2,
  ]);

  $this->essayQuestion = $this->exercise->questions()->create([
    "question_text" => "Explain the concept.",
    "type" => "free_text",
    "score_weight" => 15,
    "order" => 3,
  ]);
});

describe("Attempt Flow Tests (Start -> Submit -> Complete)", function () {
  it("student can start attempt", function () {
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->exercise->id}/attempts"),
    );

    $response
      ->assertStatus(201)
      ->assertJsonPath("data.attempt.status", "in_progress")
      ->assertJsonPath("data.attempt.user_id", $this->student->id)
      ->assertJsonPath("data.attempt.exercise_id", $this->exercise->id);

    expect($response->json("data.attempt.started_at"))->not->toBeNull();
  });

  it("cannot start multiple active attempts for same exercise", function () {
    // Start first attempt
    $this->actingAs($this->student, "api")
      ->postJson(api("/assessments/exercises/{$this->exercise->id}/attempts"))
      ->assertStatus(201);

    // Try to start second attempt while first is active
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->exercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  it("can start new attempt after completing previous", function () {
    // Start and complete first attempt
    $attempt1 = $this->actingAs($this->student, "api")
      ->postJson(api("/assessments/exercises/{$this->exercise->id}/attempts"))
      ->json("data.attempt");

    $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt1["id"]}/complete"),
    );

    // Start second attempt
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->exercise->id}/attempts"),
    );

    $response->assertStatus(201);
    expect(Attempt::where("user_id", $this->student->id)->count())->toBe(2);
  });

  it("cannot retake when allow_retake is false", function () {
    $this->exercise->update(["allow_retake" => false]);

    // Complete first attempt
    $attempt1 = $this->actingAs($this->student, "api")
      ->postJson(api("/assessments/exercises/{$this->exercise->id}/attempts"))
      ->json("data.attempt");

    $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt1["id"]}/complete"),
    );

    // Try to start second attempt
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->exercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  it("student can view their attempts (mine)", function () {
    Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->getJson(api("/assessments/attempts"));

    $response->assertStatus(200);
    expect(count($response->json("data")))->toBe(1);
  });

  it("student can submit answer with event dispatch", function () {
    Event::fake([QuestionAnswered::class]);

    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $this->mcQuestion->id,
        "selected_option_id" => $this->correctOption->id,
      ],
    );

    $response->assertStatus(200);
    Event::assertDispatched(QuestionAnswered::class);
  });

  it("can update answer by re-submitting", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    // First submission with wrong answer
    $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $this->mcQuestion->id,
        "selected_option_id" => $this->wrongOption->id,
      ],
    );

    // Update to correct answer
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $this->mcQuestion->id,
        "selected_option_id" => $this->correctOption->id,
      ],
    );

    $response->assertStatus(200);

    $answer = $attempt->answers()->where("question_id", $this->mcQuestion->id)->first();
    expect($answer->selected_option_id)->toBe($this->correctOption->id);
  });

  it("can submit essay answer", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $this->essayQuestion->id,
        "answer_text" => "This is my essay answer explaining the concept in detail.",
      ],
    );

    $response->assertStatus(200);
    expect($response->json("data.answer.answer_text"))->toContain("essay answer");
  });

  it("cannot submit answer to completed attempt", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $this->mcQuestion->id,
        "selected_option_id" => $this->correctOption->id,
      ],
    );

    $response->assertStatus(422);
  });

  it("student can complete attempt with auto-grading", function () {
    Event::fake([AttemptCompleted::class]);

    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now()->subSeconds(10),
      "total_questions" => 3,
    ]);

    // Submit correct answers for auto-graded questions
    $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $this->mcQuestion->id,
        "selected_option_id" => $this->correctOption->id,
      ],
    );

    $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $this->tfQuestion->id,
        "selected_option_id" => $this->trueOption->id,
      ],
    );

    $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $this->essayQuestion->id,
        "answer_text" => "Essay answer for manual grading.",
      ],
    );

    // Complete attempt
    $response = $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $response->assertStatus(200)->assertJsonPath("data.attempt.status", "completed");

    // Check auto-grading
    $attempt->refresh();
    expect($attempt->score)->toBe(15); // 10 + 5 for correct answers
    expect($attempt->correct_answers)->toBe(2); // MC + TF
    expect($attempt->finished_at)->not->toBeNull();
    expect($attempt->duration_seconds)->toBeGreaterThan(0);

    Event::assertDispatched(AttemptCompleted::class);
  });
});

describe("Attempt Time Limit Enforcement", function () {
  it("cannot start attempt for unpublished exercise", function () {
    $draftExercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$draftExercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  it("cannot start attempt for exercise not yet available", function () {
    $futureExercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
      "available_from" => now()->addDay(),
      "available_until" => now()->addDays(2),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$futureExercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  it("cannot start attempt for expired exercise", function () {
    $expiredExercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
      "available_from" => now()->subDays(2),
      "available_until" => now()->subDay(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$expiredExercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  it("cannot start attempt without enrollment", function () {
    $otherCourse = Course::factory()->create(["instructor_id" => $this->instructor->id]);
    $otherExercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $otherCourse->id,
      "status" => "published",
      "available_from" => now()->subDay(),
      "available_until" => now()->addDay(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$otherExercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });
});

describe("Role-Based Access Control", function () {
  it("student cannot view others attempt", function () {
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole("Student");
    Enrollment::create([
      "user_id" => $otherStudent->id,
      "course_id" => $this->course->id,
      "status" => "active",
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $otherStudent->id,
      "enrollment_id" => $otherStudent->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->getJson(
      api("/assessments/attempts/{$attempt->id}"),
    );

    $response->assertStatus(403);
  });

  it("instructor can view student attempts for their exercise", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $response = $this->actingAs($this->instructor, "api")->getJson(
      api("/assessments/attempts/{$attempt->id}"),
    );

    $response->assertStatus(200);
  });
});
