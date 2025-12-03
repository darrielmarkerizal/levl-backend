<?php

use Illuminate\Support\Facades\Config;
use Modules\Assessments\Models\AssessmentRegistration;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Services\AssessmentRegistrationService;
use Modules\Assessments\Support\DTOs\RegisterAssessmentDTO;
use Modules\Assessments\Support\Exceptions\PrerequisitesNotMetException;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Disable Scout for testing
    Config::set('scout.driver', 'null');

    createTestRoles();

    $this->service = new AssessmentRegistrationService;
    $this->user = User::factory()->create();
    $this->user->assignRole('Student');

    $this->course = Course::factory()->create();
    $this->exercise = Exercise::factory()->create([
        'scope_type' => 'course',
        'scope_id' => $this->course->id,
        'status' => 'published',
    ]);
});

describe('Prerequisite Checking', function () {
    it('checks prerequisites correctly when user has active enrollment', function () {
        // Create active enrollment
        Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $result = $this->service->checkPrerequisites($this->user, $this->exercise);

        expect($result->met)->toBeTrue();
        expect($result->missingPrerequisites)->toBeEmpty();
        expect($result->completedPrerequisites)->toContain('Course enrollment');
        expect($result->completedPrerequisites)->toContain('Active enrollment status');
    });

    it('fails prerequisite check when user has no enrollment', function () {
        $result = $this->service->checkPrerequisites($this->user, $this->exercise);

        expect($result->met)->toBeFalse();
        expect($result->missingPrerequisites)->toContain('Course enrollment required');
        expect($result->completedPrerequisites)->toBeEmpty();
    });

    it('fails prerequisite check when enrollment is not active', function () {
        // Create inactive enrollment
        Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'pending',
        ]);

        $result = $this->service->checkPrerequisites($this->user, $this->exercise);

        expect($result->met)->toBeFalse();
        expect($result->missingPrerequisites)->toContain('Active enrollment status required');
        expect($result->completedPrerequisites)->toContain('Course enrollment');
    });

    it('passes prerequisite check when enrollment is completed', function () {
        // Create completed enrollment
        Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'completed',
        ]);

        $result = $this->service->checkPrerequisites($this->user, $this->exercise);

        expect($result->met)->toBeTrue();
        expect($result->missingPrerequisites)->toBeEmpty();
    });

    it('returns true for exercises without scope', function () {
        // Create a standalone exercise (not linked to any course/unit/lesson)
        $exerciseWithoutScope = Exercise::factory()->create([
            'scope_type' => 'course',
            'scope_id' => 999999, // Non-existent course ID
        ]);

        $result = $this->service->checkPrerequisites($this->user, $exerciseWithoutScope);

        expect($result->met)->toBeTrue();
        expect($result->missingPrerequisites)->toBeEmpty();
        expect($result->completedPrerequisites)->toBeEmpty();
    });
});

describe('Assessment Registration', function () {
    it('throws exception when prerequisites are not met', function () {
        $dto = new RegisterAssessmentDTO;

        expect(fn () => $this->service->register($this->user, $this->exercise, $dto))
            ->toThrow(PrerequisitesNotMetException::class);
    });

    it('successfully registers when prerequisites are met', function () {
        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: now()->addDays(7)->toDateTimeString(),
            paymentAmount: 100.00,
            notes: 'Test registration'
        );

        $registration = $this->service->register($this->user, $this->exercise, $dto);

        expect($registration)->toBeInstanceOf(AssessmentRegistration::class);
        expect($registration->user_id)->toBe($this->user->id);
        expect($registration->exercise_id)->toBe($this->exercise->id);
        expect($registration->enrollment_id)->toBe($enrollment->id);
        expect($registration->status)->toBe(AssessmentRegistration::STATUS_CONFIRMED);
        expect($registration->prerequisites_met)->toBeTrue();
        expect($registration->prerequisites_checked_at)->not->toBeNull();
        expect($registration->payment_amount)->toBe('100.00');
        expect($registration->payment_status)->toBe(AssessmentRegistration::PAYMENT_PENDING);
        expect($registration->notes)->toBe('Test registration');
    });

    it('creates registration without payment when amount is null', function () {
        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: now()->addDays(7)->toDateTimeString()
        );

        $registration = $this->service->register($this->user, $this->exercise, $dto);

        expect($registration->payment_amount)->toBeNull();
        expect($registration->payment_status)->toBeNull();
    });

    it('saves registration to database', function () {
        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id
        );

        $registration = $this->service->register($this->user, $this->exercise, $dto);

        $this->assertDatabaseHas('assessment_registrations', [
            'id' => $registration->id,
            'user_id' => $this->user->id,
            'exercise_id' => $this->exercise->id,
            'prerequisites_met' => true,
        ]);
    });
});

describe('Slot Availability and Capacity', function () {
    it('allows registration when slot has available capacity', function () {
        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        // Create exercise with max capacity
        $exercise = Exercise::factory()->create([
            'scope_type' => 'course',
            'scope_id' => $this->course->id,
            'status' => 'published',
            'max_capacity' => 5,
        ]);

        $scheduledAt = now()->addDays(7)->toDateTimeString();

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: $scheduledAt
        );

        $registration = $this->service->register($this->user, $exercise, $dto);

        expect($registration)->toBeInstanceOf(AssessmentRegistration::class);
        expect($registration->scheduled_at->toDateTimeString())->toBe($scheduledAt);
    });

    it('throws exception when slot is full', function () {
        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        // Create exercise with max capacity of 2
        $exercise = Exercise::factory()->create([
            'scope_type' => 'course',
            'scope_id' => $this->course->id,
            'status' => 'published',
            'max_capacity' => 2,
        ]);

        $scheduledAt = now()->addDays(7)->toDateTimeString();

        // Create 2 existing registrations for the same slot
        $otherUsers = User::factory()->count(2)->create();
        foreach ($otherUsers as $otherUser) {
            $otherUser->assignRole('Student');
            $otherEnrollment = Enrollment::create([
                'user_id' => $otherUser->id,
                'course_id' => $this->course->id,
                'status' => 'active',
            ]);

            AssessmentRegistration::create([
                'user_id' => $otherUser->id,
                'exercise_id' => $exercise->id,
                'enrollment_id' => $otherEnrollment->id,
                'scheduled_at' => $scheduledAt,
                'status' => AssessmentRegistration::STATUS_CONFIRMED,
                'prerequisites_met' => true,
                'prerequisites_checked_at' => now(),
            ]);
        }

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: $scheduledAt
        );

        expect(fn () => $this->service->register($this->user, $exercise, $dto))
            ->toThrow(AssessmentFullException::class);
    });

    it('does not count cancelled registrations towards capacity', function () {
        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        // Create exercise with max capacity of 2
        $exercise = Exercise::factory()->create([
            'scope_type' => 'course',
            'scope_id' => $this->course->id,
            'status' => 'published',
            'max_capacity' => 2,
        ]);

        $scheduledAt = now()->addDays(7)->toDateTimeString();

        // Create 2 cancelled registrations for the same slot
        $otherUsers = User::factory()->count(2)->create();
        foreach ($otherUsers as $otherUser) {
            $otherUser->assignRole('Student');
            $otherEnrollment = Enrollment::create([
                'user_id' => $otherUser->id,
                'course_id' => $this->course->id,
                'status' => 'active',
            ]);

            AssessmentRegistration::create([
                'user_id' => $otherUser->id,
                'exercise_id' => $exercise->id,
                'enrollment_id' => $otherEnrollment->id,
                'scheduled_at' => $scheduledAt,
                'status' => AssessmentRegistration::STATUS_CANCELLED,
                'prerequisites_met' => true,
                'prerequisites_checked_at' => now(),
            ]);
        }

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: $scheduledAt
        );

        // Should succeed because cancelled registrations don't count
        $registration = $this->service->register($this->user, $exercise, $dto);

        expect($registration)->toBeInstanceOf(AssessmentRegistration::class);
    });

    it('allows registration when no max capacity is set', function () {
        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        // Create exercise without max capacity
        $exercise = Exercise::factory()->create([
            'scope_type' => 'course',
            'scope_id' => $this->course->id,
            'status' => 'published',
            'max_capacity' => null,
        ]);

        $scheduledAt = now()->addDays(7)->toDateTimeString();

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: $scheduledAt
        );

        $registration = $this->service->register($this->user, $exercise, $dto);

        expect($registration)->toBeInstanceOf(AssessmentRegistration::class);
    });
});

describe('Confirmation Notifications', function () {
    it('fires AssessmentRegistered event on successful registration', function () {
        Event::fake();

        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: now()->addDays(7)->toDateTimeString()
        );

        $registration = $this->service->register($this->user, $this->exercise, $dto);

        Event::assertDispatched(AssessmentRegistered::class, function ($event) use ($registration) {
            return $event->registration->id === $registration->id;
        });
    });

    it('sends confirmation email when event is fired', function () {
        Mail::fake();

        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: now()->addDays(7)->toDateTimeString()
        );

        $registration = $this->service->register($this->user, $this->exercise, $dto);

        // Manually fire the event to test the listener
        event(new AssessmentRegistered($registration));

        Mail::assertSent(AssessmentConfirmationMail::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id;
        });
    });

    it('updates confirmation_sent_at timestamp after sending email', function () {
        // Create active enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $dto = new RegisterAssessmentDTO(
            enrollmentId: $enrollment->id,
            scheduledAt: now()->addDays(7)->toDateTimeString()
        );

        $registration = $this->service->register($this->user, $this->exercise, $dto);

        // Initially, confirmation_sent_at should be null
        expect($registration->confirmation_sent_at)->toBeNull();

        // Manually fire the event to test the listener
        event(new AssessmentRegistered($registration));

        // Refresh the registration from database
        $registration->refresh();

        // Now confirmation_sent_at should be set
        expect($registration->confirmation_sent_at)->not->toBeNull();
    });
});
