<?php

namespace Modules\Forums\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reaction;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Models\ForumStatistic;
use Modules\Schemes\Models\Course;

class ForumSeeder extends Seeder
{
     
    public function run(): void
    {
        $users = User::limit(10)->get();
        $courses = Course::limit(3)->get();

        if ($users->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('No users or courses found. Please seed users and courses first.');
            return;
        }

        foreach ($courses as $course) {
            for ($i = 1; $i <= 5; $i++) {
                $this->seedThread($course, $users, $i);
            }
            $this->seedStatistics($course, $users);
        }

        $this->command->info('Forum seeder completed successfully!');
    }

    private function seedThread($course, $users, int $index): void
    {
        $author = $users->random();
        // Varied states
        $isClosed = rand(0, 10) > 8; // 20% chance
        $isResolved = rand(0, 10) > 7; // 30% chance

        $thread = Thread::create([
            'scheme_id' => $course->id,
            'author_id' => $author->id,
            'title' => "Sample Thread $index for {$course->name}",
            'content' => "This is the content of sample thread $index. It contains some discussion.",
            'is_pinned' => $index === 1,
            'is_closed' => $isClosed,
            'is_resolved' => $isResolved,
            'views_count' => rand(10, 100),
            'last_activity_at' => now()->subDays(rand(0, 7)),
        ]);

        $this->seedReplies($thread, $users, $index);
        $this->seedReactions($thread, $users, Thread::class);
        
        $thread->replies_count = $thread->replies()->count();
        $thread->save();
    }

    private function seedReplies(Thread $thread, $users, int $threadIndex): void
    {
        $replyCount = rand(3, 7);
        for ($j = 1; $j <= $replyCount; $j++) {
            $this->createReply($thread, $users, $j, $threadIndex);
        }
    }

    private function createReply(Thread $thread, $users, int $replyIndex, int $threadIndex): void
    {
        $reply = Reply::create([
            'thread_id' => $thread->id,
            'parent_id' => null,
            'author_id' => $users->random()->id,
            'content' => "This is reply $replyIndex.",
            'depth' => 0,
            'is_accepted_answer' => $replyIndex === 1 && $thread->isResolved(),
        ]);

        if (rand(0, 1) && $reply->depth < 3) {
            $this->createNestedReply($thread, $reply, $users);
        }

        if (rand(0, 1)) {
            $this->seedReactions($reply, $users, Reply::class);
        }
    }

    private function createNestedReply(Thread $thread, Reply $parent, $users): void
    {
        Reply::create([
            'thread_id' => $thread->id,
            'parent_id' => $parent->id,
            'author_id' => $users->random()->id,
            'content' => 'This is a nested reply.',
            'depth' => 1,
        ]);
    }

    private function seedReactions($model, $users, string $type): void
    {
        $count = rand(1, 3);
        for ($k = 0; $k < $count; $k++) {
            try {
                Reaction::firstOrCreate([
                    'user_id' => $users->random()->id,
                    'reactable_type' => $type,
                    'reactable_id' => $model->id,
                ], [
                    'type' => ['like', 'helpful', 'solved'][rand(0, 2)],
                ]);
            } catch (\Exception $e) {
                // Ignore duplicates
            }
        }
    }

    private function seedStatistics($course, $users): void
    {
        // Scheme-wide stats
        ForumStatistic::create([
            'scheme_id' => $course->id,
            'user_id' => null,
            'threads_count' => rand(50, 100),
            'replies_count' => rand(200, 500),
            'views_count' => rand(1000, 5000),
            'avg_response_time_minutes' => rand(30, 120),
            'response_rate' => rand(80, 100) / 100,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        // User stats
        foreach ($users->take(5) as $user) {
            ForumStatistic::create([
                'scheme_id' => $course->id,
                'user_id' => $user->id,
                'threads_count' => rand(1, 10),
                'replies_count' => rand(5, 50),
                'views_count' => rand(50, 200),
                'avg_response_time_minutes' => rand(10, 60),
                'response_rate' => rand(70, 100) / 100,
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
            ]);
        }
    }
}
