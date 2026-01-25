<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Grading\Jobs\RecalculateGradesJob;
use Modules\Learning\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Learning\Contracts\Services\QuestionServiceInterface;
use Modules\Learning\Enums\QuestionType;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Events\AnswerKeyChanged;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;

class QuestionService implements QuestionServiceInterface
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository
    ) {}

    public function createQuestion(int $assignmentId, array $data): Question
    {
        return DB::transaction(function () use ($assignmentId, $data) {
            $this->validateQuestionData($data);

            // Soft-validate weight against assignment max_score (warn only; enforce on publish)
            $this->validateQuestionWeight($assignmentId, $data['weight'] ?? 0);

            $data['assignment_id'] = $assignmentId;

            if (! isset($data['order'])) {
                $maxOrder = Question::where('assignment_id', $assignmentId)->max('order') ?? -1;
                $data['order'] = $maxOrder + 1;
            }

            // Temporarily unset options to process images after creation if needed
            // But since Spatie needs a model, we create first. 
            // We can just pass $data to create(), but options won't have image URLs yet if files are passed.
            // So we unset options from creation data if they contain files, or we update them after.
            // Easier approach: Create with data (options might have UploadedFile objects which will likely fail JSON encoding or be ignored/error), 
            // So we should unset options if we plan to process them.
            // Let's copy options and unset from data passed to create.
            
            $options = $data['options'] ?? null;
            if ($options) {
                 unset($data['options']);
            }

            $attachments = $data['attachments'] ?? null;
            if ($attachments) {
                unset($data['attachments']);
            }

            $question = $this->questionRepository->create($data);

            if ($options) {
                $this->processOptionImages($question, $options);
            }

            if ($attachments) {
                $this->processQuestionAttachments($question, $attachments);
            }

            return $question;
        });
    }

    public function updateQuestion(int $questionId, array $data, ?int $assignmentId = null): Question
    {
        return DB::transaction(function () use ($questionId, $data, $assignmentId) {
            $this->validateQuestionData($data, isUpdate: true);

            if ($assignmentId !== null) {
                $question = $this->questionRepository->find($questionId);
                if (!$question || $question->assignment_id !== $assignmentId) {
                    throw new \InvalidArgumentException(__('messages.questions.not_found'));
                }
            } else {
                 $question = $this->questionRepository->find($questionId);
            }

            $options = $data['options'] ?? null;
            if ($options) {
                 unset($data['options']);
                 $this->processOptionImages($question, $options);
            }

            $attachments = $data['attachments'] ?? null;
            if ($attachments) {
                unset($data['attachments']);
                $this->processQuestionAttachments($question, $attachments);
            }

            return $this->questionRepository->updateQuestion($questionId, $data);
        });
    }

    public function deleteQuestion(int $questionId, ?int $assignmentId = null): bool
    {
        if ($assignmentId !== null) {
            $question = $this->questionRepository->find($questionId);
            if (!$question || $question->assignment_id !== $assignmentId) {
                throw new \InvalidArgumentException(__('messages.questions.not_found'));
            }
        }

        return $this->questionRepository->deleteQuestion($questionId);
    }

    public function updateAnswerKey(int $questionId, array $answerKey, int $instructorId): void
    {
        $question = $this->questionRepository->find($questionId);

        if (! $question) {
            throw new \InvalidArgumentException("Question not found: {$questionId}");
        }

        if (! $question->canAutoGrade()) {
            throw new \InvalidArgumentException('Cannot set answer key for manual grading questions');
        }

        $oldAnswerKey = $question->answer_key ?? [];

        $this->questionRepository->updateQuestion($questionId, ['answer_key' => $answerKey]);

        $question = $this->questionRepository->find($questionId);

        AnswerKeyChanged::dispatch($question, $oldAnswerKey, $answerKey, $instructorId);

        RecalculateGradesJob::dispatch(
            $questionId,
            $oldAnswerKey,
            $answerKey,
            $instructorId
        );
    }

    public function generateQuestionSet(int $assignmentId, ?int $seed = null): Collection
    {
        $assignment = Assignment::findOrFail($assignmentId);
        $seed = $seed ?? random_int(1, PHP_INT_MAX);

        return match ($assignment->randomization_type) {
            RandomizationType::Static => $this->questionRepository->findByAssignment($assignmentId),
            RandomizationType::RandomOrder => $this->getRandomOrderQuestions($assignmentId, $seed),
            RandomizationType::Bank => $this->getBankQuestions($assignment, $seed),
            default => $this->questionRepository->findByAssignment($assignmentId),
        };
    }

    public function getQuestionsByAssignment(int $assignmentId, ?\Modules\Auth\Models\User $user = null, array $filters = []): Collection
    {
        if (! $user || ! $user->hasAnyRole(['Superadmin', 'Admin', 'Instructor'])) {
            return $this->questionRepository->findByAssignment($assignmentId);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            return $this->questionRepository->searchByAssignment($assignmentId, $filters['search']);
        }

        return \Spatie\QueryBuilder\QueryBuilder::for(Question::class)
            ->where('assignment_id', $assignmentId)
            ->allowedFilters([
                \Spatie\QueryBuilder\AllowedFilter::exact('type'),
            ])
            ->allowedSorts(['order', 'weight', 'created_at'])
            ->defaultSort('order')
            ->get();
    }

    public function reorderQuestions(int $assignmentId, array $questionIds): void
    {
        $this->questionRepository->reorder($assignmentId, $questionIds);
    }

    private function validateQuestionData(array $data, bool $isUpdate = false): void
    {
        
        if (isset($data['weight']) && $data['weight'] <= 0) {
            throw new \InvalidArgumentException('Question weight must be a positive number');
        }

        
        if (! $isUpdate && isset($data['type'])) {
            $type = $data['type'] instanceof QuestionType
                ? $data['type']
                : QuestionType::from($data['type']);

            
            if ($type->requiresOptions() && empty($data['options'])) {
                throw new \InvalidArgumentException('Options are required for this question type');
            }
        }
    }

    private function getRandomOrderQuestions(int $assignmentId, int $seed): Collection
    {
        $questions = $this->questionRepository->findByAssignment($assignmentId);

        return $questions->shuffle($seed);
    }

    private function getBankQuestions(Assignment $assignment, int $seed): Collection
    {
        $count = $assignment->question_bank_count ?? 10;

        return $this->questionRepository->findRandomFromBank(
            $assignment->id,
            $count,
            $seed
        );
    }

    private function processOptionImages(Question $question, array $options): void
    {
        $modified = false;
        foreach ($options as $key => &$option) {
             if (is_array($option) && isset($option['image']) && $option['image'] instanceof \Illuminate\Http\UploadedFile) {
                 $media = $question->addMedia($option['image'])->toMediaCollection('option_images');
                 $option['image'] = $media->getUrl();
                 $modified = true;
             }
        }
        
        $question->options = $options;
        $question->save();
    }

    private function processQuestionAttachments(Question $question, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if ($attachment instanceof \Illuminate\Http\UploadedFile) {
                $question->addMedia($attachment)->toMediaCollection('question_attachments');
            }
        }
    }

    /**
     * Validate that adding a new question with given weight won't exceed assignment's max_score
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateQuestionWeight(int $assignmentId, float $newWeight): void
    {
        $assignment = Assignment::findOrFail($assignmentId);
        $currentWeight = (float) $assignment->questions()->sum('weight');
        $maxAllowed = (float) ($assignment->max_score ?? 100);
        $totalWeight = $currentWeight + (float) $newWeight;

        if ($totalWeight > $maxAllowed) {
            \Log::warning('Question weight exceeds assignment max score (soft warning).', [
                'assignment_id' => $assignmentId,
                'current_weight' => round($currentWeight, 2),
                'new_weight' => round($newWeight, 2),
                'max_score' => $maxAllowed,
                'total_weight' => round($totalWeight, 2),
            ]);
        }
    }

    public static function computeWeightStats(int $assignmentId, ?float $additionalWeight = null): array
    {
        $assignment = Assignment::findOrFail($assignmentId);
        $currentWeight = (float) $assignment->questions()->sum('weight');
        $maxAllowed = (float) ($assignment->max_score ?? 100);
        $totalWeight = $currentWeight + (float) ($additionalWeight ?? 0.0);

        return [
            'current' => round($currentWeight, 2),
            'max' => $maxAllowed,
            'total' => round($totalWeight, 2),
            'exceeds' => $totalWeight > $maxAllowed,
        ];
    }
}
