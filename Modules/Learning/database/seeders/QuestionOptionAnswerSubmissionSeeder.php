<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Learning\Enums\QuestionType;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Enums\SubmissionStatus;

class QuestionOptionAnswerSubmissionSeeder extends Seeder
{
    private array $pregenSentences = [];
    private array $pregenWords = [];
    private array $pregenParagraphs = [];
    private array $pregenUuids = [];
    private string $createdAt;

    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        ini_set('memory_limit', '1536M');
        
        echo "Seeding questions, options, answers, and submissions...\n";

        $this->pregenerateFakeData();
        $this->createdAt = now()->toDateTimeString();

        $userCount = \DB::table('users')->count();
        $assignmentCount = \DB::table('assignments')->count();

        if ($userCount === 0) {
            echo "⚠️  No users found. Please run user seeders first.\n";
            return;
        }

        if ($assignmentCount === 0) {
            echo "⚠️  No assignments found. Please run assignment seeders first.\n";
            return;
        }

        $questionTypes = [
            QuestionType::MultipleChoice->value,
            QuestionType::Essay->value,
            QuestionType::Checkbox->value,
            QuestionType::FileUpload->value,
        ];

        $submissionStates = [
            SubmissionState::InProgress->value,
            SubmissionState::Submitted->value,
            SubmissionState::AutoGraded->value,
            SubmissionState::PendingManualGrading->value,
            SubmissionState::Graded->value,
            SubmissionState::Released->value,
        ];

        $userIds = \DB::table('users')->limit(50)->pluck('id')->toArray();
        
        $questionCount = 0;
        $submissionCount = 0;
        $answerCount = 0;
        $processedAssignments = 0;

        foreach (\DB::table('assignments')->select('id', 'title')->orderBy('id')->cursor() as $assignment) {
            $processedAssignments++;
            
            if (rand(1, 100) > 20) continue;
            
            $numQuestions = rand(2, 3);
            $questionIds = [];

            for ($i = 0; $i < $numQuestions; $i++) {
                $questionType = $questionTypes[array_rand($questionTypes)];
                $maxScore = [10, 20, 25, 50][rand(0, 3)];
                
                $questionData = [
                    'assignment_id' => $assignment->id,
                    'type' => $questionType,
                    'content' => $this->pregenSentences[array_rand($this->pregenSentences)],
                    'weight' => rand(10, 50) / 10,
                    'order' => $i + 1,
                    'max_score' => $maxScore,
                    'options' => null,
                    'answer_key' => null,
                    'max_file_size' => null,
                    'allowed_file_types' => null,
                    'allow_multiple_files' => false,
                    'created_at' => $this->createdAt,
                    'updated_at' => $this->createdAt,
                ];

                $options = null;
                switch ($questionType) {
                    case QuestionType::MultipleChoice->value:
                    case QuestionType::Checkbox->value:
                        $options = [
                            ['id' => $this->pregenUuids[array_rand($this->pregenUuids)], 'label' => $this->pregenWords[array_rand($this->pregenWords)]],
                            ['id' => $this->pregenUuids[array_rand($this->pregenUuids)], 'label' => $this->pregenWords[array_rand($this->pregenWords)]],
                            ['id' => $this->pregenUuids[array_rand($this->pregenUuids)], 'label' => $this->pregenWords[array_rand($this->pregenWords)]],
                            ['id' => $this->pregenUuids[array_rand($this->pregenUuids)], 'label' => $this->pregenWords[array_rand($this->pregenWords)]],
                        ];
                        $questionData['options'] = json_encode($options);
                        $questionData['answer_key'] = json_encode(['correct_option' => 0]);
                        break;

                    case QuestionType::Essay->value:
                        $questionData['answer_key'] = json_encode(['acceptable_answers' => [
                            $this->pregenWords[array_rand($this->pregenWords)],
                            $this->pregenWords[array_rand($this->pregenWords)],
                        ]]);
                        break;

                    case QuestionType::FileUpload->value:
                        $questionData['max_file_size'] = 10000000;
                        $questionData['allowed_file_types'] = json_encode(['pdf', 'docx']);
                        $questionData['allow_multiple_files'] = rand(0, 1) === 1;
                        break;
                }

                $questionId = \DB::table('assignment_questions')->insertGetId($questionData);
                $questionIds[] = ['id' => $questionId, 'type' => $questionType, 'options' => $options, 'maxScore' => $maxScore];
                $questionCount++;
            }

            $selectedUsers = array_slice($userIds, 0, min(3, count($userIds)));

            foreach ($selectedUsers as $userId) {
                $stateIdx = array_rand($submissionStates);
                $state = $submissionStates[$stateIdx];
                
                $status = match ($state) {
                    SubmissionState::InProgress->value => SubmissionStatus::Draft->value,
                    SubmissionState::Submitted->value => SubmissionStatus::Submitted->value,
                    default => SubmissionStatus::Graded->value,
                };

                $submissionId = \DB::table('submissions')->insertGetId([
                    'assignment_id' => $assignment->id,
                    'user_id' => $userId,
                    'status' => $status,
                    'state' => $state,
                    'submitted_at' => $state !== SubmissionState::InProgress->value ? $this->createdAt : null,
                    'attempt_number' => rand(1, 3),
                    'is_late' => rand(1, 100) <= 15,
                    'created_at' => $this->createdAt,
                    'updated_at' => $this->createdAt,
                ]);
                $submissionCount++;

                foreach ($questionIds as $q) {
                    $answerData = [
                        'submission_id' => $submissionId,
                        'question_id' => $q['id'],
                        'content' => null,
                        'selected_options' => null,
                        'file_paths' => null,
                        'score' => null,
                        'is_auto_graded' => false,
                        'feedback' => null,
                        'created_at' => $this->createdAt,
                        'updated_at' => $this->createdAt,
                    ];

                    switch ($q['type']) {
                        case QuestionType::MultipleChoice->value:
                        case QuestionType::Checkbox->value:
                            if ($q['options']) {
                                $answerData['selected_options'] = json_encode([$q['options'][rand(0, 3)]['id']]);
                            }
                            break;

                        case QuestionType::Essay->value:
                            $answerData['content'] = $this->pregenParagraphs[array_rand($this->pregenParagraphs)];
                            break;

                        case QuestionType::FileUpload->value:
                            $answerData['file_paths'] = json_encode([$this->pregenWords[array_rand($this->pregenWords)] . '.pdf']);
                            break;
                    }

                    if (in_array($state, [SubmissionState::AutoGraded->value, SubmissionState::Graded->value, SubmissionState::Released->value])) {
                        $answerData['score'] = rand(0, $q['maxScore']);
                        $answerData['is_auto_graded'] = rand(1, 100) <= 70;
                    }

                    \DB::table('answers')->insertOrIgnore($answerData);
                    $answerCount++;
                }
            }
            
            if ($processedAssignments % 50 === 0) {
                gc_collect_cycles();
                echo "Processed $processedAssignments assignments. Q: $questionCount, S: $submissionCount, A: $answerCount\n";
            }
        }

        echo "✅ Question, option, answer, and submission seeding completed!\n";
        echo "Created $questionCount questions, $submissionCount submissions, and $answerCount answers\n";
        
        $this->pregenSentences = [];
        $this->pregenWords = [];
        $this->pregenParagraphs = [];
        $this->pregenUuids = [];
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }
    
    private function pregenerateFakeData(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        
        for ($i = 0; $i < 100; $i++) {
            $this->pregenSentences[] = $faker->sentence(8);
            $this->pregenWords[] = $faker->word();
            $this->pregenParagraphs[] = $faker->paragraph(1);
            $this->pregenUuids[] = $faker->uuid();
        }
        
        unset($faker);
    }
}
