<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Assessments\Events\AssessmentRegistered;
use Modules\Assessments\Jobs\SendAssessmentReminders;
use Modules\Assessments\Mail\AssessmentConfirmationMail;
use Modules\Assessments\Mail\AssessmentReminderMail;
use Modules\Assessments\Models\AssessmentRegistration;
use Modules\Assessments\Models\Exercise;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();

    $this->student = User::factory()->create();
    $this->student->assignRole('Student');

    $this->instructor = User::factory()->create();
    $this->instructor->assignRole('Instructor');

    $this->course = Course::factory()->create(['instructor_id' => $this->instructor->id]);

    $this->enrollment = Enrollment::create([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);

    $this->exercise = Exercise::factory()->create([
        'created_by' => $this->instructor->id,
        'scope_type' => 'course',
        'scope_id' => $this->course->id,
        'status' => 'published',
        'max_capacity' => 10,
    ]);
});

describe('Complete Assessment Registration Flow', function () {
    it('completes full registration flow with prerequisites check', function () {
        Mail::fake();
        Event::fake([AssessmentRegistered::class]);

        // Step 1: Check prerequisites
        $response = $this->actingAs($this->student, 'api')
            ->getJson(api("/assessments/{$this->exercise->id}/prerequisites"));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'prerequisites_met' => true,
                ],
            ]);

        // Step 2: Get available slots
        $response = $this->actingAs($this->student, 'api')
            ->getJson(api("/assessments/{$this->exercise->id}/slots"));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'datetime',
                        'available',
                        'capacity',
                        'registered',
                        'remaining',
                    ],
                ],
            ]);

        $slots = $response->json('data');
        expect($slots)->not->toBeEmpty();
        $availableSlot = collect($slots)->firstWhere('available', true);
        expect($availableSlot)->not->toBeNull();

        // Step 3: Register for assessment
        $scheduledAt = $availableSlot['datetime'];
        $response = $this->actingAs($this->student, 'api')
            ->postJson(api("/assessments/{$this->exercise->id}/register"), [
                'enrollment_id' => $this->enrollment->id,
                'scheduled_at' => $scheduledAt,
                'notes' => 'Looking forward to this assessment',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully registered for assessment',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'exercise_id',
                    'enrollment_id',
                    'scheduled_at',
                    'status',
                    'prerequisites_met',
                ],
            ]);

        // Verify registration in database
        $this->assertDatabaseHas('assessment_registrations', [
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'enrollment_id' => $this->enrollment->id,
            'status' => AssessmentRegistration::STATUS_CONFIRMED,
            'prerequisites_met' => true,
        ]);

        // Verify event was dispatched
        Event::assertDispatched(AssessmentRegistered::class, function ($event) {
            return $event->registration->user_id === $this->student->id
                && $event->registration->exercise_id === $this->exercise->id;
        });

        // Verify confirmation email was sent
        Mail::assertSent(AssessmentConfirmationMail::class, function ($mail) {
            return $mail->hasTo($this->student->email);
        });

        // Verify confirmation timestamp was updated
        $registration = AssessmentRegistration::where('user_id', $this->student->id)
            ->where('exercise_id', $this->exercise->id)
            ->first();
        expect($registration->confirmation_sent_at)->not->toBeNull();
    });

    it('prevents registration when prerequisites are not met', function () {
        // Create a student without enrollment
        $studentWithoutEnrollment = User::factory()->create();
        $studentWithoutEnrollment->assignRole('Student');

        $response = $this->actingAs($studentWithoutEnrollment, 'api')
            ->postJson(api("/assessments/{$this->exercise->id}/register"), [
                'scheduled_at' => now()->addDays(7)->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Verify no registration was created
        $this->assertDatabaseMissing('assessment_registrations', [
            'user_id' => $studentWithoutEnrollment->id,
            'exercise_id' => $this->exercise->id,
        ]);
    });

    it('prevents registration when slot is full', function () {
        // Fill up the assessment capacity
        $scheduledTime = now()->addDays(7)->setTime(9, 0);

        for ($i = 0; $i < $this->exercise->max_capacity; $i++) {
            $user = User::factory()->create();
            $user->assignRole('Student');

            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $this->course->id,
                'status' => 'active',
            ]);

            AssessmentRegistration::factory()->confirmed()->create([
                'user_id' => $user->id,
                'exercise_id' => $this->exercise->id,
                'scheduled_at' => $scheduledTime,
            ]);
        }

        // Try to register when full
        $response = $this->actingAs($this->student, 'api')
            ->postJson(api("/assessments/{$this->exercise->id}/register"), [
                'enrollment_id' => $this->enrollment->id,
                'scheduled_at' => $scheduledTime->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment([
                'message' => "Assessment slot is full. Maximum capacity of {$this->exercise->max_capacity} reached.",
            ]);
    });
});

describe('Assessment Registration Cancellation', function () {
    it('allows cancellation within timeframe (more than 24 hours before)', function () {
        // Create a registration scheduled for 48 hours from now
        $scheduledAt = now()->addHours(48);

        $registration = AssessmentRegistration::factory()->confirmed()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'enrollment_id' => $this->enrollment->id,
            'scheduled_at' => $scheduledAt,
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->deleteJson(api("/assessment-registrations/{$registration->id}"), [
                'reason' => 'Schedule conflict',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Registration cancelled successfully',
            ]);

        // Verify status was updated
        $registration->refresh();
        expect($registration->status)->toBe(AssessmentRegistration::STATUS_CANCELLED);
        expect($registration->notes)->toContain('Schedule conflict');
    });

    it('prevents cancellation within 24 hours of scheduled time', function () {
        // Create a registration scheduled for 12 hours from now (less than 24 hours)
        $scheduledAt = now()->addHours(12);

        $registration = AssessmentRegistration::factory()->confirmed()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'enrollment_id' => $this->enrollment->id,
            'scheduled_at' => $scheduledAt,
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->deleteJson(api("/assessment-registrations/{$registration->id}"), [
                'reason' => 'Too late to cancel',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment([
                'message' => 'Cancellation must be made at least 24 hours before the scheduled assessment time',
            ]);

        // Verify status was NOT updated
        $registration->refresh();
        expect($registration->status)->toBe(AssessmentRegistration::STATUS_CONFIRMED);
    });

    it('prevents cancellation of completed assessment', function () {
        $registration = AssessmentRegistration::factory()->completed()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'enrollment_id' => $this->enrollment->id,
            'scheduled_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->deleteJson(api("/assessment-registrations/{$registration->id}"), [
                'reason' => 'Cannot cancel completed',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment([
                'message' => 'Cannot cancel registration with status: completed',
            ]);
    });

    it('prevents cancellation of in-progress assessment', function () {
        $registration = AssessmentRegistration::factory()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'enrollment_id' => $this->enrollment->id,
            'scheduled_at' => now(),
            'status' => AssessmentRegistration::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->deleteJson(api("/assessment-registrations/{$registration->id}"), [
                'reason' => 'Cannot cancel in progress',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('refunds payment when cancelling paid registration', function () {
        $scheduledAt = now()->addDays(7);

        $registration = AssessmentRegistration::factory()
            ->confirmed()
            ->paid()
            ->create([
                'user_id' => $this->student->id,
                'exercise_id' => $this->exercise->id,
                'enrollment_id' => $this->enrollment->id,
                'scheduled_at' => $scheduledAt,
            ]);

        expect($registration->payment_status)->toBe(AssessmentRegistration::PAYMENT_PAID);

        $response = $this->actingAs($this->student, 'api')
            ->deleteJson(api("/assessment-registrations/{$registration->id}"), [
                'reason' => 'Need refund',
            ]);

        $response->assertStatus(200);

        // Verify payment status was updated to refunded
        $registration->refresh();
        expect($registration->payment_status)->toBe(AssessmentRegistration::PAYMENT_REFUNDED);
    });

    it('prevents user from cancelling another users registration', function () {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('Student');

        $registration = AssessmentRegistration::factory()->confirmed()->create([
            'user_id' => $otherStudent->id,
            'exercise_id' => $this->exercise->id,
            'scheduled_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->deleteJson(api("/assessment-registrations/{$registration->id}"), [
                'reason' => 'Unauthorized attempt',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to cancel this registration',
            ]);
    });
});

describe('Assessment Reminder Notifications', function () {
    it('sends reminder notifications 24 hours before assessment', function () {
        Mail::fake();

        // Create registrations scheduled for 24 hours from now
        $targetTime = now()->addHours(24);

        $registration1 = AssessmentRegistration::factory()->confirmed()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'scheduled_at' => $targetTime,
            'reminder_sent_at' => null,
        ]);

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('Student');

        $registration2 = AssessmentRegistration::factory()->confirmed()->create([
            'user_id' => $otherStudent->id,
            'exercise_id' => $this->exercise->id,
            'scheduled_at' => $targetTime->copy()->addMinutes(30),
            'reminder_sent_at' => null,
        ]);

        // Execute the reminder job
        $job = new SendAssessmentReminders;
        $job->handle();

        // Verify reminder emails were sent
        Mail::assertSent(AssessmentReminderMail::class, 2);

        Mail::assertSent(AssessmentReminderMail::class, function ($mail) use ($registration1) {
            return $mail->hasTo($this->student->email)
                && $mail->registration->id === $registration1->id;
        });

        Mail::assertSent(AssessmentReminderMail::class, function ($mail) use ($registration2, $otherStudent) {
            return $mail->hasTo($otherStudent->email)
                && $mail->registration->id === $registration2->id;
        });

        // Verify reminder_sent_at was updated
        $registration1->refresh();
        $registration2->refresh();

        expect($registration1->reminder_sent_at)->not->toBeNull();
        expect($registration2->reminder_sent_at)->not->toBeNull();
    });

    it('does not send reminder if already sent', function () {
        Mail::fake();

        // Create registration with reminder already sent
        $targetTime = now()->addHours(24);

        AssessmentRegistration::factory()->confirmed()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'scheduled_at' => $targetTime,
            'reminder_sent_at' => now()->subHours(1), // Already sent
        ]);

        // Execute the reminder job
        $job = new SendAssessmentReminders;
        $job->handle();

        // Verify no reminder emails were sent
        Mail::assertNothingSent();
    });

    it('does not send reminder for cancelled registrations', function () {
        Mail::fake();

        // Create cancelled registration
        $targetTime = now()->addHours(24);

        AssessmentRegistration::factory()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'scheduled_at' => $targetTime,
            'status' => AssessmentRegistration::STATUS_CANCELLED,
            'reminder_sent_at' => null,
        ]);

        // Execute the reminder job
        $job = new SendAssessmentReminders;
        $job->handle();

        // Verify no reminder emails were sent
        Mail::assertNothingSent();
    });

    it('only sends reminders within 24-hour window', function () {
        Mail::fake();

        // Create registrations at different times
        $tooEarly = AssessmentRegistration::factory()->confirmed()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'scheduled_at' => now()->addHours(48), // 48 hours away
            'reminder_sent_at' => null,
        ]);

        $tooLate = AssessmentRegistration::factory()->confirmed()->create([
            'user_id' => $this->student->id,
            'exercise_id' => $this->exercise->id,
            'scheduled_at' => now()->addHours(12), // 12 hours away
            'reminder_sent_at' => null,
        ]);

        // Execute the reminder job
        $job = new SendAssessmentReminders;
        $job->handle();

        // Verify no reminder emails were sent
        Mail::assertNothingSent();

        // Verify reminder_sent_at was NOT updated
        $tooEarly->refresh();
        $tooLate->refresh();

        expect($tooEarly->reminder_sent_at)->toBeNull();
        expect($tooLate->reminder_sent_at)->toBeNull();
    });
});
