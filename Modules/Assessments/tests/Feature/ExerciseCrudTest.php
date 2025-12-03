<?php

use Illuminate\Support\Facades\Event;
use Modules\Assessments\Events\AttemptCompleted;
use Modules\Assessments\Events\ExerciseCreated;
use Modules\Assessments\Events\GradingCompleted;
use Modules\Assessments\Events\QuestionAnswered;
use Modules\Assessments\Models\Answer;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Models\Question;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
  createTestRoles();

  $this->superadmin = User::factory()->create();
  $this->superadmin->assignRole("Superadmin");

  $this->admin = User::factory()->create();
  $this->admin->assignRole("Admin");

  $this->instructor = User::factory()->create();
  $this->instructor->assignRole("Instructor");

  $this->student = User::factory()->create();
  $this->student->assignRole("Student");

  $this->course = Course::factory()->create(["instructor_id" => $this->instructor->id]);
  $this->course->admins()->attach($this->admin->id);

  Enrollment::create([
    "user_id" => $this->student->id,
    "course_id" => $this->course->id,
    "status" => "active",
  ]);
});

describe("Exercise CRUD Tests (Admin Only)", function () {
  it("admin can create exercise", function () {
    Event::fake([ExerciseCreated::class]);

    $response = $this->actingAs($this->admin, "api")->postJson(api("/assessments/exercises"), [
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "title" => "Test Exercise",
      "description" => "Test description",
      "type" => "quiz",
      "time_limit_minutes" => 30,
      "max_score" => 100,
      "allow_retake" => true,
    ]);

    $response
      ->assertStatus(201)
      ->assertJsonPath("data.exercise.title", "Test Exercise")
      ->assertJsonPath("data.exercise.status", "draft")
      ->assertJsonPath("data.exercise.allow_retake", true);

    Event::assertDispatched(ExerciseCreated::class);
  });

  it("instructor can create exercise", function () {
    $response = $this->actingAs($this->instructor, "api")->postJson(api("/assessments/exercises"), [
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "title" => "Instructor Quiz",
      "type" => "quiz",
    ]);

    $response->assertStatus(201);
  });

  it("student cannot create exercise", function () {
    $response = $this->actingAs($this->student, "api")->postJson(api("/assessments/exercises"), [
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "title" => "Malicious Quiz",
      "type" => "quiz",
    ]);

    $response->assertStatus(403);
  });

  it("can list exercises with filters", function () {
    Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "draft",
      "type" => "quiz",
    ]);

    Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
      "type" => "exam",
    ]);

    // Filter by status
    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises") . "?status=draft",
    );
    $response->assertStatus(200);
    expect(count($response->json("data")))->toBe(1);

    // Filter by type
    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises") . "?type=exam",
    );
    $response->assertStatus(200);
    expect(count($response->json("data")))->toBe(1);

    // Filter by scope
    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises") . "?scope_type=course&scope_id=" . $this->course->id,
    );
    $response->assertStatus(200);
    expect(count($response->json("data")))->toBe(2);
  });

  it("can view exercise details with questions", function () {
    $exercise = Exercise::factory()->create(["created_by" => $this->instructor->id]);
    $exercise->questions()->create([
      "question_text" => "Test question?",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->getJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response
      ->assertStatus(200)
      ->assertJsonPath("data.exercise.id", $exercise->id)
      ->assertJsonCount(1, "data.exercise.questions");
  });

  it("can update draft exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}"),
      [
        "title" => "Updated Title",
        "allow_retake" => false,
      ],
    );

    $response
      ->assertStatus(200)
      ->assertJsonPath("data.exercise.title", "Updated Title")
      ->assertJsonPath("data.exercise.allow_retake", false);
  });

  it("cannot update published exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "published",
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}"),
      [
        "title" => "Cannot Update",
      ],
    );

    $response->assertStatus(403);
  });

  it("can delete draft exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(204);
    $this->assertDatabaseMissing("exercises", ["id" => $exercise->id]);
  });

  it("cannot delete published exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "published",
    ]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(403);
  });

  it("can publish exercise with questions", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);
    $exercise->questions()->create([
      "question_text" => "Question 1?",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}/publish"),
    );

    $response->assertStatus(200)->assertJsonPath("data.exercise.status", "published");
  });

  it("cannot publish exercise without questions", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}/publish"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["questions"]);
  });
});

describe("Question CRUD Tests", function () {
  it("instructor can add question to exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor, "api")->postJson(
      api("/assessments/exercises/{$exercise->id}/questions"),
      [
        "question_text" => "What is 2+2?",
        "type" => "multiple_choice",
        "score_weight" => 10,
        "is_required" => true,
      ],
    );

    $response->assertStatus(201)->assertJsonPath("data.question.question_text", "What is 2+2?");

    // Check total_questions updated
    $exercise->refresh();
    expect($exercise->total_questions)->toBe(1);
  });

  it("supports all question types", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $types = ["multiple_choice", "free_text", "file_upload", "true_false"];

    foreach ($types as $type) {
      $response = $this->actingAs($this->instructor, "api")->postJson(
        api("/assessments/exercises/{$exercise->id}/questions"),
        [
          "question_text" => "Question of type {$type}",
          "type" => $type,
          "score_weight" => 5,
        ],
      );

      $response->assertStatus(201);
    }

    $exercise->refresh();
    expect($exercise->total_questions)->toBe(4);
  });

  it("can update question", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);
    $question = $exercise->questions()->create([
      "question_text" => "Original",
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

    $response
      ->assertStatus(200)
      ->assertJsonPath("data.question.question_text", "Updated question")
      ->assertJsonPath("data.question.score_weight", 15);
  });

  it("can delete question", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);
    $question = $exercise->questions()->create([
      "question_text" => "To be deleted",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/questions/{$question->id}"),
    );

    $response->assertStatus(204);
    $this->assertDatabaseMissing("questions", ["id" => $question->id]);

    // Check total_questions updated
    $exercise->refresh();
    expect($exercise->total_questions)->toBe(0);
  });

  it("student cannot add questions", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$exercise->id}/questions"),
      [
        "question_text" => "Malicious question",
        "type" => "multiple_choice",
        "score_weight" => 10,
      ],
    );

    $response->assertStatus(403);
  });
});

describe("Question Options Tests", function () {
  it("can add options to question", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);
    $question = $exercise->questions()->create([
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

    $response
      ->assertStatus(201)
      ->assertJsonPath("data.option.option_text", "Option A")
      ->assertJsonPath("data.option.is_correct", true);
  });

  it("can update option", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);
    $question = $exercise->questions()->create([
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

    $response
      ->assertStatus(200)
      ->assertJsonPath("data.option.option_text", "Updated")
      ->assertJsonPath("data.option.is_correct", true);
  });

  it("can delete option", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);
    $question = $exercise->questions()->create([
      "question_text" => "Q",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $option = $question->options()->create([
      "option_text" => "Remove me",
      "is_correct" => false,
    ]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/options/{$option->id}"),
    );

    $response->assertStatus(204);
    $this->assertDatabaseMissing("question_options", ["id" => $option->id]);
  });
});
