<?php

declare(strict_types=1);

namespace Modules\Forums\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Mention;
use Modules\Forums\Models\Reaction;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Schemes\Models\Course;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting forum seeding...');

        $users = User::limit(20)->get();
        $courses = Course::all();

        if ($users->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('Not enough users or courses. Need at least 20 users and at least 1 course.');
            return;
        }

        foreach ($courses as $course) {
            $this->seedCourseForums($course, $users);
        }

        $this->seedUserMentions($users, $courses);

        $this->command->info('✓ Forum seeding completed successfully!');
    }

    private function seedCourseForums(Course $course, $users): void
    {
        $this->command->line("  → Seeding forum for course: {$course->title}");

        for ($i = 1; $i <= 10; $i++) {
            $thread = $this->createThread(
                $course->id,
                $users,
                "Course Discussion: {$course->title} - Topic $i"
            );

            $this->createReplies($thread, $users, rand(3, 8));
            $this->createReactions($thread, $users);
        }
    }

    private function createThread(int $courseId, $users, string $title): Thread
    {
        $author = $users->random();
        $hasMention = rand(0, 100) < 40;
        $mentionedUsers = $hasMention ? $this->selectMentionedUsers($users, $author->id, rand(1, 3)) : collect();
        $content = $this->generateThreadContent($mentionedUsers);

        $thread = Thread::create([
            'course_id' => $courseId,
            'author_id' => $author->id,
            'title' => $title,
            'content' => $content,
            'is_pinned' => rand(1, 10) > 8,
            'is_closed' => rand(1, 10) > 8,
            'is_resolved' => rand(1, 10) > 7,
            'views_count' => rand(5, 100),
            'replies_count' => 0,
            'last_activity_at' => now()->subDays(rand(0, 14)),
        ]);

        foreach ($mentionedUsers as $user) {
            $this->createMention($thread, $user);
        }

        return $thread;
    }

    private function createReplies(Thread $thread, $users, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $author = $users->random();
            $hasMention = rand(0, 100) < 30;
            $mentionedUsers = $hasMention ? $this->selectMentionedUsers($users, $author->id, rand(1, 3)) : collect();

            $reply = Reply::create([
                'thread_id' => $thread->id,
                'author_id' => $author->id,
                'content' => $this->generateReplyContent($mentionedUsers),
                'parent_id' => null,
                'depth' => 0,
                'is_accepted_answer' => false,
            ]);

            foreach ($mentionedUsers as $user) {
                $this->createMention($reply, $user);
            }

            if (rand(0, 1) && $i < $count) {
                $this->createNestedReply($thread, $reply, $users, rand(1, 2));
            }

            $this->createReactions($reply, $users);
        }

        $thread->increment('replies_count', $count);
    }

    private function createNestedReply(Thread $thread, Reply $parent, $users, int $depth): void
    {
        if ($depth > 3) {
            return;
        }

        $author = $users->random();
        $hasMention = rand(0, 100) < 25;
        $mentionedUsers = $hasMention ? $this->selectMentionedUsers($users, $author->id, 1) : collect();

        $reply = Reply::create([
            'thread_id' => $thread->id,
            'parent_id' => $parent->id,
            'author_id' => $author->id,
            'content' => $this->generateReplyContent($mentionedUsers),
            'depth' => $depth,
            'is_accepted_answer' => false,
        ]);

        foreach ($mentionedUsers as $user) {
            $this->createMention($reply, $user);
        }

        $thread->increment('replies_count');

        if (rand(0, 1) && $depth < 3) {
            $this->createNestedReply($thread, $reply, $users, $depth + 1);
        }
    }

    private function selectMentionedUsers($users, int $excludeUserId, int $count = 1)
    {
        $availableUsers = $users->where('id', '!=', $excludeUserId);
        
        if ($availableUsers->isEmpty()) {
            return collect();
        }

        $count = min($count, $availableUsers->count());
        return $availableUsers->random($count);
    }

    private function createMention($model, User $user): void
    {
        Mention::create([
            'user_id' => $user->id,
            'mentionable_type' => $model::class,
            'mentionable_id' => $model->id,
        ]);
    }

    private function createReactions($model, $users): void
    {
        $reactionCount = rand(0, 3);

        for ($i = 0; $i < $reactionCount; $i++) {
            try {
                $type = ['like', 'helpful', 'solved'][rand(0, 2)];

                Reaction::firstOrCreate([
                    'user_id' => $users->random()->id,
                    'reactable_type' => $model::class,
                    'reactable_id' => $model->id,
                    'type' => $type,
                ]);
            } catch (\Exception $e) {
            }
        }
    }

    private function generateThreadContent($mentionedUsers): string
    {
        $nonMentionContents = [
            'Bagaimana cara solve masalah ini? Saya sudah coba berbagai cara tapi masih error.',
            'Apakah ada yang bisa explain konsep ini dengan cara yang lebih simple?',
            'Saya punya pertanyaan tentang implementation dari topik yang kemarin dibahas.',
            'Bisa minta bantuan untuk debug code saya? Saya stuck di bagian ini.',
            'Ada yang punya reference atau tutorial yang bisa help saya understand ini better?',
            'Saya ingin share solution yang mungkin bisa membantu teman-teman lainnya.',
            'Bisa explain lebih detail tentang edge case yang mungkin terjadi di sini?',
            'Saya sempat baca documentation tapi masih kurang jelas, ada yang bisa clarify?',
            'Kenapa hasil output saya berbeda dengan yang di tutorial? Ada yang salah dengan setup saya?',
            'Saya berhasil implement fitur ini, tapi performance-nya kurang optimal. Ada saran?',
            'Apakah best practice untuk handle error di kasus seperti ini?',
            'Saya menemukan bug di library yang kita pakai, ada workaround yang bisa digunakan?',
        ];

        $content = $nonMentionContents[array_rand($nonMentionContents)];

        if ($mentionedUsers->isNotEmpty()) {
            $mentionPrefixes = [
                ' cc ',
                ' FYI ',
                ' ',
                "\n\n",
            ];
            $prefix = $mentionPrefixes[array_rand($mentionPrefixes)];
            $mentions = $mentionedUsers->map(fn($u) => "@{$u->username}")->implode(' ');
            $content .= $prefix . $mentions;
        }

        return $content;
    }

    private function generateReplyContent($mentionedUsers): string
    {
        $nonMentionReplies = [
            'Coba approach dengan cara ini, semoga membantu!',
            'Saya pernah hadapi masalah yang sama, solution-nya adalah dengan mengubah konfigurasi di file .env',
            'Good point! Aku setuju dengan perspective ini.',
            'Benar sekali, ini adalah best practice untuk kasus seperti ini.',
            'Terima kasih atas input-nya, sangat membantu!',
            'Interesting! Aku belum pernah pikir dari angle itu.',
            'This makes sense now, thank you for explaining!',
            'Setuju banget dengan penjelasan ini, very clear!',
            'Kalau boleh saran, coba refactor code-nya supaya lebih readable.',
            'Aku pernah baca artikel tentang ini, wait aku cariin link-nya.',
            'Hmm, sepertinya ada typo di code kamu. Coba cek lagi bagian variable declaration.',
            'Solusi yang bagus! Tapi mungkin perlu consider juga untuk edge case ketika data kosong.',
        ];

        $content = $nonMentionReplies[array_rand($nonMentionReplies)];

        if ($mentionedUsers->isNotEmpty()) {
            $mentionPositions = ['prefix', 'suffix'];
            $position = $mentionPositions[array_rand($mentionPositions)];
            $mentions = $mentionedUsers->map(fn($u) => "@{$u->username}")->implode(' ');

            if ($position === 'prefix') {
                $content = $mentions . ' ' . $content;
            } else {
                $content .= ' ' . $mentions;
            }
        }

        return $content;
    }

    private function seedUserMentions($users, $courses): void
    {
        $this->command->line("  → Ensuring all users have at least one mention...");

        $mentionedUserIds = Mention::distinct('user_id')->pluck('user_id')->toArray();
        $unmentionedUsers = $users->whereNotIn('id', $mentionedUserIds);

        if ($unmentionedUsers->isEmpty()) {
            $this->command->info("    ✓ All users already have mentions");
            return;
        }

        $this->command->info("    → Creating threads to mention {$unmentionedUsers->count()} users");

        foreach ($unmentionedUsers as $targetUser) {
            $course = $courses->random();
            $author = $users->where('id', '!=', $targetUser->id)->random();
            
            $otherMentions = rand(0, 2);
            $mentionedUsers = collect([$targetUser]);
            
            if ($otherMentions > 0) {
                $additionalUsers = $this->selectMentionedUsers(
                    $users, 
                    $author->id, 
                    $otherMentions
                )->filter(fn($u) => $u->id !== $targetUser->id);
                
                $mentionedUsers = $mentionedUsers->merge($additionalUsers)->unique('id');
            }

            $content = $this->generateThreadContent($mentionedUsers);

            $thread = Thread::create([
                'forumable_type' => Course::class,
                'forumable_id' => $course->id,
                'author_id' => $author->id,
                'title' => "Discussion: Question for @{$targetUser->username}",
                'content' => $content,
                'is_pinned' => false,
                'is_closed' => false,
                'is_resolved' => false,
                'views_count' => rand(5, 50),
                'replies_count' => 0,
                'last_activity_at' => now()->subDays(rand(0, 7)),
            ]);

            foreach ($mentionedUsers as $user) {
                $this->createMention($thread, $user);
            }

            $this->command->info("    ✓ Created thread mentioning @{$targetUser->username}" . 
                ($mentionedUsers->count() > 1 ? " (+" . ($mentionedUsers->count() - 1) . " others)" : ""));
        }
    }
}
