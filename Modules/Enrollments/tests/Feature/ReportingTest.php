<?php

namespace Modules\Enrollments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\CourseProgress;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $instructor;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('Instructor');

        $this->student = User::factory()->create();
        $this->student->assignRole('Student');

        $this->course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
        ]);
    }

    /** @test */
    public function instructor_can_view_course_completion_rate()
    {
        // Create some enrollments with different statuses
        Enrollment::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
        $completedEnrollment = Enrollment::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'completed',
        ]);
        CourseProgress::factory()->create([
            'enrollment_id' => $completedEnrollment->id,
            'status' => 'completed',
            'progress_percent' => 100,
        ]);

        $response = $this->actingAs($this->instructor, 'api')
            ->getJson("/api/v1/courses/{$this->course->slug}/reports/completion-rate");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'course' => ['id', 'slug', 'title'],
                    'statistics' => [
                        'total_enrolled',
                        'active_count',
                        'completed_count',
                        'pending_count',
                        'cancelled_count',
                        'completion_rate',
                        'avg_progress_percent',
                    ],
                ],
            ]);

        $this->assertEquals(2, $response->json('data.statistics.total_enrolled'));
        $this->assertEquals(1, $response->json('data.statistics.completed_count'));
        $this->assertEquals(50.0, $response->json('data.statistics.completion_rate'));
    }

    /** @test */
    public function student_cannot_view_completion_rate()
    {
        $response = $this->actingAs($this->student, 'api')
            ->getJson("/api/v1/courses/{$this->course->slug}/reports/completion-rate");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_enrollment_funnel()
    {
        // Create enrollments with different statuses
        Enrollment::factory()->count(5)->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
        Enrollment::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'status' => 'completed',
        ]);
        Enrollment::factory()->count(1)->create([
            'course_id' => $this->course->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/reports/enrollment-funnel?course_id={$this->course->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'funnel' => [
                        'total_requests',
                        'pending' => ['count', 'percentage'],
                        'active' => ['count', 'percentage'],
                        'completed' => ['count', 'percentage'],
                        'cancelled' => ['count', 'percentage'],
                    ],
                ],
            ]);

        $this->assertEquals(8, $response->json('data.funnel.total_requests'));
        $this->assertEquals(5, $response->json('data.funnel.active.count'));
        $this->assertEquals(2, $response->json('data.funnel.completed.count'));
    }

    /** @test */
    public function student_cannot_view_enrollment_funnel()
    {
        $response = $this->actingAs($this->student, 'api')
            ->getJson('/api/v1/reports/enrollment-funnel');

        $response->assertStatus(403);
    }

    /** @test */
    public function instructor_can_export_enrollments_csv()
    {
        Enrollment::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->instructor, 'api')
            ->get("/api/v1/courses/{$this->course->slug}/exports/enrollments-csv");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('content-disposition'));

        // Check CSV content
        $content = $response->streamedContent();
        $this->assertStringContainsString('Student Name', $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('Status', $content);
    }

    /** @test */
    public function student_cannot_export_csv()
    {
        $response = $this->actingAs($this->student, 'api')
            ->get("/api/v1/courses/{$this->course->slug}/exports/enrollments-csv");

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_instructor_cannot_view_other_course_reports()
    {
        $otherInstructor = User::factory()->create();
        $otherInstructor->assignRole('Instructor');

        $response = $this->actingAs($otherInstructor, 'api')
            ->getJson("/api/v1/courses/{$this->course->slug}/reports/completion-rate");

        $response->assertStatus(403);
    }

    /** @test */
    public function superadmin_can_view_all_reports()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('Superadmin');

        $response = $this->actingAs($superadmin, 'api')
            ->getJson("/api/v1/courses/{$this->course->slug}/reports/completion-rate");

        $response->assertStatus(200);

        $response = $this->actingAs($superadmin, 'api')
            ->getJson('/api/v1/reports/enrollment-funnel');

        $response->assertStatus(200);
    }
}
