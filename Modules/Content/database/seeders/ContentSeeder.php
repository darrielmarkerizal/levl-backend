<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\ContentCategory;
use Modules\Content\Models\News;
use Modules\Schemes\Models\Course;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        // Create categories
        $categories = [
            ['name' => 'Teknologi', 'slug' => 'teknologi', 'description' => 'Berita seputar teknologi'],
            ['name' => 'Pendidikan', 'slug' => 'pendidikan', 'description' => 'Berita seputar pendidikan'],
            ['name' => 'Pengumuman Umum', 'slug' => 'pengumuman-umum', 'description' => 'Pengumuman umum'],
            ['name' => 'Event', 'slug' => 'event', 'description' => 'Informasi event'],
        ];

        foreach ($categories as $category) {
            ContentCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        // Get admin user
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->first();

        if (! $admin) {
            $this->command->warn('No admin user found. Skipping content seeding.');

            return;
        }

        // Create sample announcements
        $announcements = [
            [
                'title' => 'Selamat Datang di Platform LMS',
                'content' => 'Selamat datang di platform Learning Management System kami. Kami berkomitmen untuk memberikan pengalaman belajar terbaik.',
                'status' => 'published',
                'target_type' => 'all',
                'priority' => 'high',
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Pemeliharaan Sistem Terjadwal',
                'content' => 'Sistem akan menjalani pemeliharaan rutin pada hari Minggu, 10 Desember 2025 pukul 02:00 - 06:00 WIB.',
                'status' => 'published',
                'target_type' => 'all',
                'priority' => 'normal',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Pengumuman untuk Instruktur',
                'content' => 'Mohon semua instruktur untuk mengupdate materi kursus sebelum akhir bulan.',
                'status' => 'published',
                'target_type' => 'role',
                'target_value' => 'instructor',
                'priority' => 'normal',
                'published_at' => now()->subDay(),
            ],
        ];

        foreach ($announcements as $announcementData) {
            Announcement::firstOrCreate(
                ['title' => $announcementData['title']],
                array_merge($announcementData, ['author_id' => $admin->id])
            );
        }

        // Create sample news
        $newsArticles = [
            [
                'title' => 'Platform LMS Meluncurkan Fitur Baru',
                'slug' => 'platform-lms-meluncurkan-fitur-baru',
                'excerpt' => 'Kami dengan bangga mengumumkan peluncuran fitur-fitur baru yang akan meningkatkan pengalaman belajar Anda.',
                'content' => 'Platform LMS kami terus berkembang dengan menambahkan fitur-fitur inovatif. Fitur terbaru termasuk sistem notifikasi yang lebih baik, dashboard yang diperbarui, dan integrasi dengan berbagai tools pembelajaran.',
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Tips Belajar Efektif Online',
                'slug' => 'tips-belajar-efektif-online',
                'excerpt' => 'Pelajari cara memaksimalkan pembelajaran online Anda dengan tips-tips praktis ini.',
                'content' => 'Belajar online memerlukan disiplin dan strategi yang tepat. Berikut adalah beberapa tips untuk membantu Anda belajar lebih efektif: 1) Buat jadwal belajar yang konsisten, 2) Siapkan ruang belajar yang nyaman, 3) Aktif berpartisipasi dalam diskusi, 4) Manfaatkan semua resources yang tersedia.',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => 'Webinar: Masa Depan Pendidikan Digital',
                'slug' => 'webinar-masa-depan-pendidikan-digital',
                'excerpt' => 'Bergabunglah dengan webinar kami tentang tren terbaru dalam pendidikan digital.',
                'content' => 'Kami mengundang Anda untuk mengikuti webinar eksklusif tentang masa depan pendidikan digital. Webinar ini akan membahas tren teknologi terkini, metodologi pembelajaran inovatif, dan bagaimana platform LMS dapat membantu mencapai tujuan pembelajaran Anda.',
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subDays(1),
            ],
        ];

        $categoryIds = ContentCategory::pluck('id')->toArray();

        foreach ($newsArticles as $newsData) {
            $news = News::firstOrCreate(
                ['slug' => $newsData['slug']],
                array_merge($newsData, ['author_id' => $admin->id])
            );

            // Attach random categories
            if ($news->wasRecentlyCreated) {
                $randomCategories = array_rand(array_flip($categoryIds), min(2, count($categoryIds)));
                $news->categories()->sync(is_array($randomCategories) ? $randomCategories : [$randomCategories]);
            }
        }

        // Create course-specific announcement if courses exist
        $course = Course::first();
        if ($course) {
            Announcement::firstOrCreate(
                ['title' => 'Pengumuman Kursus: '.$course->name],
                [
                    'author_id' => $admin->id,
                    'course_id' => $course->id,
                    'content' => 'Ini adalah pengumuman khusus untuk kursus '.$course->name.'. Mohon perhatikan jadwal dan deadline tugas.',
                    'status' => 'published',
                    'target_type' => 'course',
                    'priority' => 'normal',
                    'published_at' => now()->subDays(1),
                ]
            );
        }

        $this->command->info('Content seeded successfully!');
    }
}
