<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubmissionFileSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection()->disableQueryLog();

        echo "Seeding submission files...\n";

        $fileUploadAssignmentIds = DB::table('assignment_questions')
            ->where('type', 'file_upload')
            ->pluck('assignment_id')
            ->unique()
            ->toArray();

        if (empty($fileUploadAssignmentIds)) {
            echo "⚠️  No assignments with file upload questions found.\n";
            return;
        }

        echo "   📁 Found " . count($fileUploadAssignmentIds) . " assignments\n";

        $totalSubmissions = DB::table('submissions')
            ->whereIn('assignment_id', $fileUploadAssignmentIds)
            ->count();

        if ($totalSubmissions === 0) {
            echo "⚠️  No submissions found.\n";
            return;
        }

        echo "   📋 Processing $totalSubmissions submissions...\n";

        $fileCount = 0;
        $processed = 0;
        $filesToInsert = [];
        $batchSize = 500;

        foreach (DB::table('submissions')
            ->whereIn('assignment_id', $fileUploadAssignmentIds)
            ->orderBy('id')
            ->cursor() as $submission) {

            if (rand(1, 100) > 60) {
                $processed++;
                continue;
            }

            $numFiles = rand(1, 3);
            for ($i = 0; $i < $numFiles; $i++) {
                $filesToInsert[] = [
                    'submission_id' => $submission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $fileCount++;
            }

            if (count($filesToInsert) >= $batchSize) {
                DB::table('submission_files')->insertOrIgnore($filesToInsert);
                $filesToInsert = [];
            }

            $processed++;
            if ($processed % 5000 === 0) {
                echo "      ✓ $processed/$totalSubmissions ($fileCount files)\n";
                gc_collect_cycles();
            }
        }

        if (!empty($filesToInsert)) {
            DB::table('submission_files')->insertOrIgnore($filesToInsert);
        }

        echo "✅ Created $fileCount submission files\n";
        DB::connection()->enableQueryLog();
    }
}