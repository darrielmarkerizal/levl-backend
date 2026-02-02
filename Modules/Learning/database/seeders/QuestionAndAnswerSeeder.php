<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;
use Modules\Learning\Models\Answer;
use Modules\Learning\Enums\QuestionType;

class QuestionAndAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates comprehensive question and answer data:
     * - Adds questions to existing assignments
     * - Creates answers for existing submissions
     * - Ensures some submissions have ungraded answers for the grading queue
     */
    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        ini_set('memory_limit', '1536M');
        
        echo "Seeding questions and answers...\n";

        // Cleanup invalid question types from previous failed runs
        \Illuminate\Support\Facades\DB::table('assignment_questions')
            ->where('type', 'short_answer')
            ->delete();

        $faker = \Faker\Factory::create('id_ID');
        $pregenSentences = [];
        $pregenWords = [];
        $pregenParagraphs = [];
        $pregenUuids = [];
        $pregenFilenames = [];
        $createdAt = now()->toDateTimeString();
        
        for ($i = 0; $i < 200; $i++) {
            $pregenSentences[] = $faker->sentence(10);
            $pregenWords[] = $faker->word();
            $pregenParagraphs[] = $faker->paragraph(2);
            $pregenUuids[] = $faker->uuid();
            $pregenFilenames[] = $faker->sha256();
        }
        unset($faker);

        if (!Assignment::exists()) {
            echo "⚠️  No assignments found. Skipping question and answer seeding.\n";
            return;
        }

        if (!Submission::exists()) {
            echo "⚠️  No submissions found. Skipping answer seeding.\n";
            return;
        }

        $questionCount = 0;
        $answerCount = 0;

        Assignment::withCount('questions')->chunkById(500, function ($assignments) use (&$questionCount, $pregenSentences, $pregenWords, $pregenUuids, $createdAt) {
            $questions = [];

            foreach ($assignments as $assignment) {
                $numQuestions = rand(2, 5);

                for ($i = 0; $i < $numQuestions; $i++) {
                    $questionTypes = ['essay', 'multiple_choice', 'file_upload'];
                    $questionType = $questionTypes[array_rand($questionTypes)];
                    
                    $questionData = [
                        'assignment_id' => $assignment->id,
                        'type' => $questionType,
                        'content' => $pregenSentences[array_rand($pregenSentences)],
                        'weight' => rand(10, 50) / 10,
                        'order' => $i + 1,
                        'max_score' => [10, 20, 25, 50][rand(0, 3)],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    $questionData['options'] = null;
                    $questionData['answer_key'] = null;
                    $questionData['max_file_size'] = null;
                    $questionData['allowed_file_types'] = null;
                    $questionData['allow_multiple_files'] = false;

                    switch ($questionType) {
                        case 'multiple_choice':
                            $questionData['options'] = json_encode([
                                ['id' => $pregenUuids[array_rand($pregenUuids)], 'label' => $pregenWords[array_rand($pregenWords)]],
                                ['id' => $pregenUuids[array_rand($pregenUuids)], 'label' => $pregenWords[array_rand($pregenWords)]],
                                ['id' => $pregenUuids[array_rand($pregenUuids)], 'label' => $pregenWords[array_rand($pregenWords)]],
                                ['id' => $pregenUuids[array_rand($pregenUuids)], 'label' => $pregenWords[array_rand($pregenWords)]],
                            ]);
                            $questionData['answer_key'] = json_encode(['correct_option' => 0]);
                            break;

                        case 'file_upload':
                            $questionData['max_file_size'] = 10000000;
                            $questionData['allowed_file_types'] = json_encode(['pdf', 'docx']);
                            $questionData['allow_multiple_files'] = true;
                            break;
                    }

                    $questions[] = $questionData;
                    $questionCount++;
                }
            }

            if (!empty($questions)) {
                foreach (array_chunk($questions, 500) as $chunk) {
                    \Illuminate\Support\Facades\DB::table('assignment_questions')->insertOrIgnore($chunk);
                }
            }
            unset($questions);

            echo "Processed chunk. Questions created: $questionCount\n";
            gc_collect_cycles();
        });

        Submission::with('assignment.questions')->chunkById(300, function ($submissions) use (&$answerCount, $pregenParagraphs, $pregenFilenames, $createdAt) {
            $answers = [];

            foreach ($submissions as $submission) {
                if (!$submission->assignment || !$submission->assignment->questions || $submission->assignment->questions->isEmpty()) {
                    continue;
                }

                foreach ($submission->assignment->questions as $question) {
                    if (rand(1, 100) <= 30) continue;

                    $answerData = [
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'score' => null,
                        'is_auto_graded' => false,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    switch ($question->type->value) {
                        case 'multiple_choice':
                            $options = $question->options;
                            if ($options) {
                                $answerData['selected_options'] = json_encode([
                                    $options[rand(0, count($options)-1)]['id']
                                ]);
                            }
                            break;
                        
                            
                        case 'essay':
                            $answerData['content'] = $pregenParagraphs[array_rand($pregenParagraphs)];
                            break;
                            
                        case 'file_upload':
                            $answerData['file_paths'] = json_encode([
                                'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                            ]);
                            $answerData['file_metadata'] = json_encode([
                                'total_size' => rand(100000, 2000000),
                                'file_count' => 1,
                            ]);
                            break;
                    }

                    $answers[] = $answerData;
                    $answerCount++;
                }

                if (count($answers) >= 500) {
                    \Illuminate\Support\Facades\DB::table('answers')->insertOrIgnore($answers);
                    $answers = null;
                    unset($answers);
                    $answers = [];
                    gc_collect_cycles();
                }
            }

            if (!empty($answers)) {
                foreach (array_chunk($answers, 500) as $chunk) {
                    \Illuminate\Support\Facades\DB::table('answers')->insertOrIgnore($chunk);
                }
            }
            unset($answers);

            echo "Processed chunk. Answers created: $answerCount\n";
            gc_collect_cycles();
        });

        echo "✅ Question and answer seeding completed!\n";
        echo "Created $questionCount questions with $answerCount answers\n";
        
        $this->updateSubmissionStates();
        
        unset($pregenSentences, $pregenWords, $pregenParagraphs, $pregenUuids, $pregenFilenames);
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }

    private function updateSubmissionStates(): void
    {
        echo "Updating submission states for grading queue...\n";

        // Update submissions that have answers but are not yet in the correct state for grading
        $updated = \DB::table('submissions')
            ->join('answers', 'submissions.id', '=', 'answers.submission_id')
            ->where('submissions.status', 'submitted')
            ->whereNull('submissions.state')
            ->whereNull('answers.score') // Answers without scores need grading
            ->distinct('submissions.id')
            ->limit(100) // Update only 100 to avoid overloading
            ->update(['submissions.state' => 'pending_manual_grading']);

        echo "✅ Updated $updated submissions to pending_manual_grading state\n";
    }
}