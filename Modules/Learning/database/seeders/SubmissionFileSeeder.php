<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use Modules\Learning\Models\SubmissionFile;
use Illuminate\Support\Facades\Storage;

class SubmissionFileSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $this->command->info("Seeding submission files with Object Storage uploads...");

        $fileUploadAssignmentIds = DB::table('assignment_questions')
            ->where('type', 'file_upload')
            ->pluck('assignment_id')
            ->unique()
            ->toArray();

        if (empty($fileUploadAssignmentIds)) {
            $this->command->warn("⚠️  No assignments with file upload questions found.");
            return;
        }

        $this->command->info("   📁 Found " . count($fileUploadAssignmentIds) . " assignments");

        // Get submissions that need files
        $submissions = DB::table('submissions')
            ->whereIn('assignment_id', $fileUploadAssignmentIds)
            ->orderBy('id')
            ->get(); // We use get() here to iterate easier, assuming not millions suitable for cursor yet for complex logic

        if ($submissions->isEmpty()) {
            $this->command->warn("⚠️  No submissions found.");
            return;
        }

        $totalSubmissions = $submissions->count();
        $this->command->info("   📋 Processing $totalSubmissions submissions...");

        $fileCount = 0;
        $processed = 0;
        $bar = $this->command->getOutput()->createProgressBar($totalSubmissions);
        $bar->start();

        foreach ($submissions as $submission) {
            // Randomly decide if this submission has files (simulate some empty or failed ones? nah, lets give most files)
            // User requested robust seeding, let's say 80% have files
            if (rand(1, 100) > 80) {
                $processed++;
                $bar->advance();
                continue;
            }

            $numFiles = rand(1, 2); // 1 or 2 files per submission

            for ($i = 0; $i < $numFiles; $i++) {
                try {
                    $submissionFile = SubmissionFile::create([
                        'submission_id' => $submission->id,
                    ]);

                    // Create a dummy file
                    $fileName = "submission_{$submission->id}_file_{$i}.txt";
                    $fileContent = "This is a dummy submission file for Submission ID: {$submission->id}.\nGenerated at: " . now();
                    
                    // Use a temporary path
                    $tempPath = sys_get_temp_dir() . '/' . $fileName;
                    file_put_contents($tempPath, $fileContent);

                    // Upload to Media Library (Object Storage)
                    $submissionFile->addMedia($tempPath)
                        ->usingFileName($fileName)
                        ->toMediaCollection('file');

                    $fileCount++;
                    
                    // Cleanup temp file
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }

                } catch (\Exception $e) {
                    $this->command->error("\nFailed to upload file for submission {$submission->id}: " . $e->getMessage());
                }
            }

            $processed++;
            $bar->advance();
            
            // Clean memory occasionally
            if ($processed % 100 === 0) {
                 gc_collect_cycles();
            }
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ Created and uploaded $fileCount submission files to Object Storage.");
        
        DB::connection()->enableQueryLog();
    }
}