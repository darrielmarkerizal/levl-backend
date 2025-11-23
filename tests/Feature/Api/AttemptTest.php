<?php

use Modules\Auth\Models\User;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Models\Question;
use Modules\Assessments\Models\Attempt;
use Modules\Schemes\Models\Course;
use Modules\Enrollments\Models\Enrollment;

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

  $this->draftExercise = Exercise::factory()->create([
    "created_by" => $this->instructor->id,
    "scope_type" => "course",
    "scope_id" => $this->course->id,
    "status" => "draft",
  ]);

  $this->exercise = Exercise::factory()->create([
    "created_by" => $this->instructor->id,
    "scope_type" => "course",
    "scope_id" => $this->course->id,
    "status" => "published",
    "available_from" => now()->subDay(),
    "available_until" => now()->addDay(),
  ]);
});

describe("Question Management", function () {
  it("instructor can add question to their exercise", function () {
    $response = $this->actingAs($this->instructor, "api")->postJson(
      api("/assessments/exercises/{$this->draftExercise->id}/questions"),
      [
        "question_text" => "What is 2+2?",
        "type" => "multiple_choice",
        "score_weight" => 10,
      ],
    );

    $response->assertStatus(201)->assertJsonPath("data.question.question_text", "What is 2+2?");
  });

  it("student cannot add question to exercise", function () {
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->draftExercise->id}/questions"),
      [
        "question_text" => "Malicious question",
        "type" => "multiple_choice",
        "score_weight" => 10,
      ],
    );

    $response->assertStatus(403);
  });

  it("can update question", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Original question",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/questions/{$question->id}"),
      [
        "question_text" => "Updated question",
        "score_weight" => 15,
      ],
    );

    $response->assertStatus(200)->assertJsonPath("data.question.question_text", "Updated question");
  });

  it("can delete question", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "To be deleted",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/questions/{$question->id}"),
    );

    $response->assertStatus(204);
    $this->assertDatabaseMissing("questions", ["id" => $question->id]);
  });
});

describe("Question Options", function () {
  it("instructor can add options to question", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Multiple choice",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->postJson(
      api("/assessments/questions/{$question->id}/options"),
      [
        "option_text" => "Option A",
        "is_correct" => true,
      ],
    );

    $response->assertStatus(201)->assertJsonPath("data.option.option_text", "Option A");
  });

  it("can update option", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Q",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $option = $question->options()->create([
      "option_text" => "Original",
      "is_correct" => false,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/options/{$option->id}"),
      [
        "option_text" => "Updated",
        "is_correct" => true,
      ],
    );

    $response->assertStatus(200)->assertJsonPath("data.option.option_text", "Updated");
  });

  it("can delete option", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Q",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $option = $question->options()->create(["option_text" => "Remove me", "is_correct" => false]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/options/{$option->id}"),
    );

    $response->assertStatus(204);
  });
});

describe("Attempt Management", function () {
  it("student can start attempt", function () {
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->exercise->id}/attempts"),
    );

    $response->assertStatus(201)->assertJsonPath("data.attempt.status", "in_progress");
  });

  it("student can list their attempts", function () {
    Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->getJson(api("/assessments/attempts"));

    $response->assertStatus(200)->assertJsonCount(1, "data.items");
  });

  it("student can view their attempt", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->getJson(
      api("/assessments/attempts/{$attempt->id}"),
    );

    $response->assertStatus(200);
  });

  it("student can submit answer", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $question = $this->exercise->questions()->create([
      "question_text" => "Q?",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $option = $question->options()->create(["option_text" => "A", "is_correct" => true]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
        "selected_option_id" => $option->id,
      ],
    );

    $response->assertStatus(200);
  });

  it("student can complete attempt", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 1,
    ]);

    $response = $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $response->assertStatus(200)->assertJsonPath("data.attempt.status", "completed");
  });
});
