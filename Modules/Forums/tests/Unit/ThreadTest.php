<?php

namespace Modules\Forums\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reaction;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Schemes\Models\Course;
use Tests\TestCase;

class ThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_belongs_to_scheme()
    {
        $course = Course::factory()->create();
        $thread = Thread::factory()->create(['scheme_id' => $course->id]);

        $this->assertInstanceOf(Course::class, $thread->scheme);
        $this->assertEquals($course->id, $thread->scheme->id);
    }

    public function test_thread_belongs_to_author()
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['author_id' => $user->id]);

        $this->assertInstanceOf(User::class, $thread->author);
        $this->assertEquals($user->id, $thread->author->id);
    }

    public function test_thread_has_many_replies()
    {
        $thread = Thread::factory()->create();
        Reply::factory()->count(3)->create(['thread_id' => $thread->id]);

        $this->assertCount(3, $thread->replies);
    }

    public function test_thread_has_many_reactions()
    {
        $thread = Thread::factory()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            Reaction::create([
                'user_id' => $user->id,
                'reactable_type' => Thread::class,
                'reactable_id' => $thread->id,
                'type' => 'like',
            ]);
        }

        $this->assertCount(3, $thread->reactions);
    }

    public function test_scope_for_scheme()
    {
        $course = Course::factory()->create();
        Thread::factory()->count(3)->create(['scheme_id' => $course->id]);
        Thread::factory()->count(2)->create(); // Different scheme

        $threads = Thread::forScheme($course->id)->get();

        $this->assertCount(3, $threads);
    }

    public function test_scope_pinned()
    {
        Thread::factory()->count(2)->pinned()->create();
        Thread::factory()->count(3)->create(['is_pinned' => false]);

        $pinnedThreads = Thread::pinned()->get();

        $this->assertCount(2, $pinnedThreads);
        $this->assertTrue($pinnedThreads->every(fn ($t) => $t->is_pinned));
    }

    public function test_scope_resolved()
    {
        Thread::factory()->count(2)->resolved()->create();
        Thread::factory()->count(3)->create(['is_resolved' => false]);

        $resolvedThreads = Thread::resolved()->get();

        $this->assertCount(2, $resolvedThreads);
        $this->assertTrue($resolvedThreads->every(fn ($t) => $t->is_resolved));
    }

    public function test_scope_closed()
    {
        Thread::factory()->count(2)->closed()->create();
        Thread::factory()->count(3)->create(['is_closed' => false]);

        $closedThreads = Thread::closed()->get();

        $this->assertCount(2, $closedThreads);
        $this->assertTrue($closedThreads->every(fn ($t) => $t->is_closed));
    }

    public function test_is_pinned_method()
    {
        $pinnedThread = Thread::factory()->pinned()->create();
        $normalThread = Thread::factory()->create(['is_pinned' => false]);

        $this->assertTrue($pinnedThread->isPinned());
        $this->assertFalse($normalThread->isPinned());
    }

    public function test_is_closed_method()
    {
        $closedThread = Thread::factory()->closed()->create();
        $openThread = Thread::factory()->create(['is_closed' => false]);

        $this->assertTrue($closedThread->isClosed());
        $this->assertFalse($openThread->isClosed());
    }

    public function test_is_resolved_method()
    {
        $resolvedThread = Thread::factory()->resolved()->create();
        $unresolvedThread = Thread::factory()->create(['is_resolved' => false]);

        $this->assertTrue($resolvedThread->isResolved());
        $this->assertFalse($unresolvedThread->isResolved());
    }

    public function test_increment_views()
    {
        $thread = Thread::factory()->create(['views_count' => 5]);

        $thread->incrementViews();

        $this->assertEquals(6, $thread->fresh()->views_count);
    }

    public function test_update_last_activity()
    {
        $thread = Thread::factory()->create(['last_activity_at' => now()->subHour()]);
        $oldActivity = $thread->last_activity_at;

        sleep(1);
        $thread->updateLastActivity();

        $this->assertNotEquals($oldActivity, $thread->fresh()->last_activity_at);
    }

    public function test_soft_delete()
    {
        $thread = Thread::factory()->create();
        $threadId = $thread->id;

        $thread->delete();

        $this->assertSoftDeleted('threads', ['id' => $threadId]);
        $this->assertNotNull($thread->fresh()->deleted_at);
    }
}
