<?php

namespace Modules\Forums\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Forums\Models\ForumStatistic;
use Modules\Schemes\Models\Course;
use Tests\TestCase;

class ForumStatisticTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistic_belongs_to_scheme()
    {
        $course = Course::factory()->create();
        $statistic = ForumStatistic::create([
            'scheme_id' => $course->id,
            'threads_count' => 10,
            'replies_count' => 50,
            'views_count' => 200,
            'period_start' => Carbon::now()->startOfMonth(),
            'period_end' => Carbon::now()->endOfMonth(),
        ]);

        $this->assertInstanceOf(Course::class, $statistic->scheme);
        $this->assertEquals($course->id, $statistic->scheme->id);
    }

    public function test_statistic_belongs_to_user()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $statistic = ForumStatistic::create([
            'scheme_id' => $course->id,
            'user_id' => $user->id,
            'threads_count' => 5,
            'replies_count' => 20,
            'period_start' => Carbon::now()->startOfMonth(),
            'period_end' => Carbon::now()->endOfMonth(),
        ]);

        $this->assertInstanceOf(User::class, $statistic->user);
        $this->assertEquals($user->id, $statistic->user->id);
    }

    public function test_scope_for_scheme()
    {
        $course = Course::factory()->create();
        ForumStatistic::factory()->count(3)->create(['scheme_id' => $course->id]);
        ForumStatistic::factory()->count(2)->create(); // Different scheme

        $statistics = ForumStatistic::forScheme($course->id)->get();

        $this->assertCount(3, $statistics);
    }

    public function test_scope_for_user()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        ForumStatistic::factory()->count(2)->create([
            'scheme_id' => $course->id,
            'user_id' => $user->id,
        ]);
        ForumStatistic::factory()->count(3)->create([
            'scheme_id' => $course->id,
        ]); // Different user

        $statistics = ForumStatistic::forUser($user->id)->get();

        $this->assertCount(2, $statistics);
    }

    public function test_scope_scheme_wide()
    {
        $course = Course::factory()->create();

        ForumStatistic::factory()->count(2)->create([
            'scheme_id' => $course->id,
            'user_id' => null,
        ]);
        ForumStatistic::factory()->count(3)->create([
            'scheme_id' => $course->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $statistics = ForumStatistic::schemeWide()->get();

        $this->assertCount(2, $statistics);
        $this->assertTrue($statistics->every(fn ($s) => $s->user_id === null));
    }

    public function test_scope_for_period()
    {
        $course = Course::factory()->create();
        $periodStart = Carbon::parse('2025-01-01');
        $periodEnd = Carbon::parse('2025-01-31');

        ForumStatistic::factory()->create([
            'scheme_id' => $course->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        ForumStatistic::factory()->create([
            'scheme_id' => $course->id,
            'period_start' => Carbon::parse('2025-02-01'),
            'period_end' => Carbon::parse('2025-02-28'),
        ]);

        $statistics = ForumStatistic::forPeriod($periodStart, $periodEnd)->get();

        $this->assertCount(1, $statistics);
    }

    public function test_casts_are_correct()
    {
        $course = Course::factory()->create();
        $statistic = ForumStatistic::create([
            'scheme_id' => $course->id,
            'threads_count' => 10,
            'replies_count' => 50,
            'views_count' => 200,
            'avg_response_time_minutes' => 45,
            'response_rate' => 85.50,
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-31',
        ]);

        $this->assertIsInt($statistic->threads_count);
        $this->assertIsInt($statistic->replies_count);
        $this->assertIsInt($statistic->views_count);
        $this->assertIsInt($statistic->avg_response_time_minutes);
        $this->assertIsString($statistic->response_rate); // Decimal cast returns string
        $this->assertInstanceOf(Carbon::class, $statistic->period_start);
        $this->assertInstanceOf(Carbon::class, $statistic->period_end);
    }
}
