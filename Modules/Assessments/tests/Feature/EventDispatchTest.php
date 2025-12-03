<?php

use Illuminate\Support\Facades\Event;
use Modules\Assessments\Events\AttemptCompleted;
use Modules\Assessments\Events\ExerciseCreated;
use Modules\Assessments\Events\GradingCompleted;
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
});

describe("Event Dispatch Tests", function () {
  it("dispatches ExerciseCreated when exercise is created", function () {
    Event::fake([ExerciseCreated::class]);

    $response = $this->actingAs($this->instructor, "api")->postJson(api("/assessments/exercises"), [
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "title" => "New Exercise",
      "type" => "quiz",
    ]);

    $response->assertStatus(201);

    Event::assertDispatched(ExerciseCreated::class, function ($event) {
      return $event->exercise->title === "New Exercise";
    });
  });

  it("dispatches QuestionAnswered when answer is submitted", function () {
    Event::fake([QuestionAnswered::class]);

    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
      "available_from" => now()->subDay(),
      "available_until" => now()->addDay(),
    ]);

    $question = $exercise->questions()->create([
      "question_text" => "Test question?",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $option = $question->options()->create([
      "option_text" => "Answer",
      "is_correct" => true,
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
        "selected_option_id" => $option->id,
      ],
    );

    $response->assertStatus(200);

    Event::assertDispatched(QuestionAnswered::class, function ($event) use ($question) {
      return $event->answer->question_id === $question->id;
    });
  });

  it("dispatches AttemptCompleted when attempt is completed", function () {
    Event::fake([AttemptCompleted::class]);

    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
      "available_from" => now()->subDay(),
      "available_until" => now()->addDay(),
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $response->assertStatus(200);

    Event::assertDispatched(AttemptCompleted::class, function ($event) use ($attempt) {
      return $event->attempt->id === $attempt->id;
    });
  });

  it("dispatches GradingCompleted when score is updated", function () {
    Event::fake([GradingCompleted::class]);

    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
      "score" => 50,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/score"),
      [
        "score" => 85,
        "feedback" => "Great work!",
      ],
    );

    $response->assertStatus(200);

    Event::assertDispatched(GradingCompleted::class, function ($event) use ($attempt) {
      return $event->attempt->id === $attempt->id && $event->attempt->score === 85;
    });
  });
});

describe("Event Payload Tests", function () {
  it("ExerciseCreated contains correct exercise data", function () {
    Event::fake([ExerciseCreated::class]);

    $this->actingAs($this->instructor, "api")->postJson(api("/assessments/exercises"), [
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "title" => "Event Test Exercise",
      "description" => "Testing event payload",
      "type" => "exam",
      "time_limit_minutes" => 90,
      "allow_retake" => false,
    ]);

    Event::assertDispatched(ExerciseCreated::class, function ($event) {
      $exercise = $event->exercise;
      return $exercise->title === "Event Test Exercise" &&
        $exercise->type === "exam" &&
        $exercise->time_limit_minutes === 90 &&
        $exercise->allow_retake === false;
    });
  });

  it("AttemptCompleted contains graded attempt data", function () {
    Event::fake([AttemptCompleted::class]);

    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
      "available_from" => now()->subDay(),
      "available_until" => now()->addDay(),
    ]);

    $question = $exercise->questions()->create([
      "question_text" => "Test?",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $correctOption = $question->options()->create([
      "option_text" => "Correct",
      "is_correct" => true,
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 1,
    ]);

    // Submit correct answer
    $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
        "selected_option_id" => $correctOption->id,
      ],
    );

    // Complete attempt
    $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    Event::assertDispatched(AttemptCompleted::class, function ($event) {
      $attempt = $event->attempt;
      return $attempt->status === "completed" &&
        $attempt->score === 10 &&
        $attempt->correct_answers === 1;
    });
  });
});
