<?php

namespace Modules\Forums\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reaction;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reaction_belongs_to_user()
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();

        $reaction = Reaction::create([
            'user_id' => $user->id,
            'reactable_type' => Thread::class,
            'reactable_id' => $thread->id,
            'type' => 'like',
        ]);

        $this->assertInstanceOf(User::class, $reaction->user);
        $this->assertEquals($user->id, $reaction->user->id);
    }

    public function test_reaction_morphs_to_thread()
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();

        $reaction = Reaction::create([
            'user_id' => $user->id,
            'reactable_type' => Thread::class,
            'reactable_id' => $thread->id,
            'type' => 'like',
        ]);

        $this->assertInstanceOf(Thread::class, $reaction->reactable);
        $this->assertEquals($thread->id, $reaction->reactable->id);
    }

    public function test_reaction_morphs_to_reply()
    {
        $user = User::factory()->create();
        $reply = Reply::factory()->create();

        $reaction = Reaction::create([
            'user_id' => $user->id,
            'reactable_type' => Reply::class,
            'reactable_id' => $reply->id,
            'type' => 'helpful',
        ]);

        $this->assertInstanceOf(Reply::class, $reaction->reactable);
        $this->assertEquals($reply->id, $reaction->reactable->id);
    }

    public function test_toggle_adds_reaction_when_not_exists()
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();

        $added = Reaction::toggle($user->id, Thread::class, $thread->id, 'like');

        $this->assertTrue($added);
        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'reactable_type' => Thread::class,
            'reactable_id' => $thread->id,
            'type' => 'like',
        ]);
    }

    public function test_toggle_removes_reaction_when_exists()
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();

        Reaction::create([
            'user_id' => $user->id,
            'reactable_type' => Thread::class,
            'reactable_id' => $thread->id,
            'type' => 'like',
        ]);

        $added = Reaction::toggle($user->id, Thread::class, $thread->id, 'like');

        $this->assertFalse($added);
        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'reactable_type' => Thread::class,
            'reactable_id' => $thread->id,
            'type' => 'like',
        ]);
    }

    public function test_get_types_returns_all_reaction_types()
    {
        $types = Reaction::getTypes();

        $this->assertCount(3, $types);
        $this->assertContains('like', $types);
        $this->assertContains('helpful', $types);
        $this->assertContains('solved', $types);
    }

    public function test_is_valid_type()
    {
        $this->assertTrue(Reaction::isValidType('like'));
        $this->assertTrue(Reaction::isValidType('helpful'));
        $this->assertTrue(Reaction::isValidType('solved'));
        $this->assertFalse(Reaction::isValidType('invalid'));
    }

    public function test_unique_constraint_prevents_duplicate_reactions()
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();

        Reaction::create([
            'user_id' => $user->id,
            'reactable_type' => Thread::class,
            'reactable_id' => $thread->id,
            'type' => 'like',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Reaction::create([
            'user_id' => $user->id,
            'reactable_type' => Thread::class,
            'reactable_id' => $thread->id,
            'type' => 'like',
        ]);
    }
}
