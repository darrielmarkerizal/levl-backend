<?php

declare(strict_types=1);

namespace Modules\Gamification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Gamification\Models\Badge;
use Modules\Gamification\Enums\BadgeType;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Gamification Badges...');

        $badges = [
            // Achievements
            [
                'code' => 'first_step',
                'name' => 'Langkah Pertama',
                'description' => 'Menyelesaikan pelajaran pertama Anda.',
                'type' => BadgeType::Achievement,
                'threshold' => 1,
            ],
            [
                'code' => 'quiz_master',
                'name' => 'Quiz Master',
                'description' => 'Mendapatkan nilai sempurna pada kuis.',
                'type' => BadgeType::Achievement,
                'threshold' => 1,
            ],
            [
                'code' => 'submission_streak',
                'name' => 'Rajin Kumpul Tugas',
                'description' => 'Mengumpulkan tugas 3 hari berturut-turut.',
                'type' => BadgeType::Achievement,
                'threshold' => 3,
            ],
            [
                'code' => 'forum_contributor',
                'name' => 'Kontributor Forum',
                'description' => 'Membuat 5 postingan diskusi yang bermanfaat.',
                'type' => BadgeType::Achievement,
                'threshold' => 5,
            ],
            
            // Levels / Ranks
            [
                'code' => 'rookie',
                'name' => 'Pendatang Baru',
                'description' => 'Mencapai Level 5.',
                'type' => BadgeType::Milestone,
                'threshold' => 5,
            ],
            [
                'code' => 'veteran',
                'name' => 'Veteran',
                'description' => 'Mencapai Level 10.',
                'type' => BadgeType::Milestone,
                'threshold' => 10,
            ],
            [
                'code' => 'elite',
                'name' => 'Elite',
                'description' => 'Mencapai Level 20.',
                'type' => BadgeType::Milestone,
                'threshold' => 20,
            ],
            [
                'code' => 'legend',
                'name' => 'Legenda',
                'description' => 'Mencapai Level 50.',
                'type' => BadgeType::Milestone,
                'threshold' => 50,
            ],

            // Completions
            [
                'code' => 'course_finisher',
                'name' => 'Penyelesai Kursus',
                'description' => 'Menyelesaikan satu kursus penuh.',
                'type' => BadgeType::Completion,
                'threshold' => 1,
            ],
            [
                'code' => 'fast_learner',
                'name' => 'Pembelajar Cepat',
                'description' => 'Menyelesaikan modul dalam waktu kurang dari 1 jam.',
                'type' => BadgeType::Completion,
                'threshold' => 1,
            ],
        ];

        $this->command->info('Downloading placeholder icon...');
        $tempPath = sys_get_temp_dir() . '/badge_placeholder.jpg';
        
        // Download once if not exists (or always to be fresh? lets check existence to speed up)
        // actually user said "download dulu 1", implied download once.
        try {
             $content = file_get_contents('https://picsum.photos/128/128');
             file_put_contents($tempPath, $content);
        } catch (\Exception $e) {
            $this->command->error("Failed to download placeholder: " . $e->getMessage());
            // Fallback or exit? usage of picsum might fail. 
            // Let's create a blank image if fetch fails or just let it fail?
            // User wants to ensure upload.
            return;
        }

        foreach ($badges as $badgeData) {
            $badge = Badge::updateOrCreate(
                ['code' => $badgeData['code']],
                $badgeData
            );

            // Re-upload if missing or recently created
            if ($badge->wasRecentlyCreated || $badge->getMedia('icon')->isEmpty()) {
                $badge->clearMediaCollection('icon');
                
                try {
                    $badge->addMedia($tempPath)
                        ->preservingOriginal()
                        ->withCustomProperties(['seeded' => true])
                        ->toMediaCollection('icon');
                } catch (\Exception $e) {
                     $this->command->error("Failed to attach media to {$badge->code}: " . $e->getMessage());
                }
            }
        }
        
        // Clean up temp file
        @unlink($tempPath);

        $this->command->info('✅ Badges seeded successfully with icons.');
    }
}
