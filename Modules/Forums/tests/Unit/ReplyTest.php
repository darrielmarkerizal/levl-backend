<?php

namespace Modules\Forums\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reaction;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_belongs_to_thread()
    {
        $thread = Thread::factory()->create();
        $reply = Reply::factory()->create(['thread_id' => $thread->id]);

        $this->assertInstanceOf(Thread::class, $reply->thread);
        $this->assertEquals($thread->id, $reply->thread->id);
    }

    public function test_reply_belongs_to_author()
    {
        $user = User::factory()->create();
        $reply = Reply::factory()->create(['author_id' => $user->id]);

        $this->assertInstanceOf(User::class, $reply->author);
        $this->assertEquals($user->id, $reply->author->id);
    }

    public function test_reply_can_have_parent()
    {
        $thread = Thread::factory()->create();
        $parentReply = Reply::factory()->create(['thread_id' => $thread->id]);
        $childReply = Reply::factory()->nested($parentReply)->create();

        $this->assertInstanceOf(Reply::class, $childReply->parent);
        $this->assertEquals($parentReply->id, $childReply->parent->id);
    }

    public function test_reply_can_have_children()
    {
        $thread = Thread::factory()->create();
        $parentReply = Reply::factory()->create(['thread_id' => $thread->id]);
        Reply::factory()->count(3)->nested($parentReply)->create();

        $this->assertCount(3, $parentReply->children);
    }

    public function test_reply_has_many_reactions()
    {
        $reply = Reply::factory()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            Reaction::create([
                'user_id' => $user->id,
                'reactable_type' => Reply::class,
                'reactable_id' => $reply->id,
                'type' => 'helpful',
            ]);
        }

        $this->assertCount(3, $reply->reactions);
    }

    public function test_depth_is_calculated_automatically()
    {
        $thread = Thread::factory()->create();
        $level0 = Reply::factory()->create(['thread_id' => $thread->id]);
        $level1 = Reply::factory()->nested($level0)->create();
        $level2 = Reply::factory()->nested($level1)->create();

        $this->assertEquals(0, $level0->depth);
        $this->assertEquals(1, $level1->depth);
        $this->assertEquals(2, $level2->depth);
    }

    public function test_is_accepted_answer_method()
    {
        $acceptedReply = Reply::factory()->accepted()->create();
        $normalReply = Reply::factory()->create(['is_accepted_answer' => false]);

        $this->assertTrue($acceptedReply->isAcceptedAnswer());
        $this->assertFalse($normalReply->isAcceptedAnswer());
    }

    public function test_get_depth_method()
    {
        $thread = Thread::factory()->create();
        $parentReply = Reply::factory()->create(['thread_id' => $thread->id]);
        $childReply = Reply::factory()->nested($parentReply)->create();

        $this->assertEquals(0, $parentReply->getDepth());
        $this->assertEquals(1, $childReply->getDepth());
    }

    public function test_can_have_children_method()
    {
        $thread = Thread::factory()->create();

        // Reply at depth 4 can have children (4 < 5)
        $reply = Reply::factory()->create([
            'thread_id' => $thread->id,
            'depth' => 4,
        ]);
        $this->assertTrue($reply->canHaveChildren());

        // Reply at max depth (5) cannot have children (5 < 5 = false)
        $maxDepthReply = Reply::factory()->make([
            'thread_id' => $thread->id,
        ]);
        $maxDepthReply->depth = Reply::MAX_DEPTH;
        $maxDepthReply->saveQuietly(); // Save without events

        $this->assertFalse($maxDepthReply->canHaveChildren());
        $this->assertEquals(Reply::MAX_DEPTH, $maxDepthReply->depth);
    }

    public function test_scope_top_level()
    {
        $thread = Thread::factory()->create();
        $topLevel = Reply::factory()->count(3)->create(['thread_id' => $thread->id]);
        $nested = Reply::factory()->nested($topLevel->first())->create();

        $topLevelReplies = Reply::topLevel()->get();

        $this->assertCount(3, $topLevelReplies);
        $this->assertTrue($topLevelReplies->every(fn ($r) => $r->parent_id === null));
    }

    public function test_scope_accepted()
    {
        Reply::factory()->count(2)->accepted()->create();
        Reply::factory()->count(3)->create(['is_accepted_answer' => false]);

        $acceptedReplies = Reply::accepted()->get();

        $this->assertCount(2, $acceptedReplies);
        $this->assertTrue($acceptedReplies->every(fn ($r) => $r->is_accepted_answer));
    }

    public function test_soft_delete()
    {
        $reply = Reply::factory()->create();
        $replyId = $reply->id;

        $reply->delete();

        $this->assertSoftDeleted('replies', ['id' => $replyId]);
        $this->assertNotNull($reply->fresh()->deleted_at);
    }
}
