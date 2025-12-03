<?php

namespace Modules\Forums\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reaction;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Schemes\Models\Course;

class ForumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users and courses
        $users = User::limit(10)->get();
        $courses = Course::limit(3)->get();

        if ($users->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('No users or courses found. Please seed users and courses first.');

            return;
        }

        foreach ($courses as $course) {
            // Create 5 threads per course
            for ($i = 1; $i <= 5; $i++) {
                $author = $users->random();

                $thread = Thread::create([
                    'scheme_id' => $course->id,
                    'author_id' => $author->id,
                    'title' => "Sample Thread $i for {$course->name}",
                    'content' => "This is the content of sample thread $i. It contains some discussion about the course topics and questions from students.",
                    'is_pinned' => $i === 1, // Pin first thread
                    'is_closed' => false,
                    'is_resolved' => false,
                    'views_count' => rand(10, 100),
                    'last_activity_at' => now()->subDays(rand(0, 7)),
                ]);

                // Create 3-7 replies per thread
                $replyCount = rand(3, 7);
                for ($j = 1; $j <= $replyCount; $j++) {
                    $replyAuthor = $users->random();

                    $reply = Reply::create([
                        'thread_id' => $thread->id,
                        'parent_id' => null,
                        'author_id' => $replyAuthor->id,
                        'content' => "This is reply $j to the thread. It provides some insights or asks follow-up questions.",
                        'depth' => 0,
                        'is_accepted_answer' => $j === 1 && $i % 2 === 0, // Mark first reply as accepted for even threads
                    ]);

                    // Create 1-2 nested replies
                    if (rand(0, 1)) {
                        $nestedAuthor = $users->random();
                        Reply::create([
                            'thread_id' => $thread->id,
                            'parent_id' => $reply->id,
                            'author_id' => $nestedAuthor->id,
                            'content' => 'This is a nested reply providing additional information.',
                            'depth' => 1,
                        ]);
                    }

                    // Add some reactions
                    if (rand(0, 1)) {
                        $reactionUser = $users->random();
                        Reaction::create([
                            'user_id' => $reactionUser->id,
                            'reactable_type' => Reply::class,
                            'reactable_id' => $reply->id,
                            'type' => ['like', 'helpful', 'solved'][rand(0, 2)],
                        ]);
                    }
                }

                // Update thread reply count
                $thread->replies_count = $thread->replies()->count();
                $thread->save();

                // Add reactions to thread
                for ($k = 0; $k < rand(1, 3); $k++) {
                    $reactionUser = $users->random();
                    try {
                        Reaction::create([
                            'user_id' => $reactionUser->id,
                            'reactable_type' => Thread::class,
                            'reactable_id' => $thread->id,
                            'type' => ['like', 'helpful', 'solved'][rand(0, 2)],
                        ]);
                    } catch (\Exception $e) {
                        // Skip if duplicate
                    }
                }
            }
        }

        $this->command->info('Forum seeder completed successfully!');
    }
}
