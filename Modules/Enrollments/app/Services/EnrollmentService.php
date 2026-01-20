<?php

declare(strict_types=1);

namespace Modules\Enrollments\Services;

use App\Contracts\EnrollmentKeyHasherInterface;
use App\Support\Helpers\UrlHelper;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BusinessException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\User;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Contracts\Services\EnrollmentServiceInterface;
use Modules\Enrollments\DTOs\CreateEnrollmentDTO;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Events\EnrollmentCreated;
use Modules\Enrollments\Mail\AdminEnrollmentNotificationMail;
use Modules\Enrollments\Mail\StudentEnrollmentActiveMail;
use Modules\Enrollments\Mail\StudentEnrollmentApprovedMail;
use Modules\Enrollments\Mail\StudentEnrollmentDeclinedMail;
use Modules\Enrollments\Mail\StudentEnrollmentPendingMail;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(
        private EnrollmentRepositoryInterface $repository,
        private EnrollmentKeyHasherInterface $keyHasher
    ) {}

    public function paginateByCourse(int $courseId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = request()->input('search');

        $builder = QueryBuilder::for(Enrollment::class)
            ->where('course_id', $courseId);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts([
                'enrolled_at', 
                'completed_at', 
                'created_at',
                AllowedSort::callback('priority', function ($query, $descending) {
                    $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                          ->orderBy('created_at', 'desc');
                }),
            ])
            ->defaultSort('priority')
            ->paginate($perPage);
    }

    public function paginateByCourseIds(array $courseIds, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = request()->input('search');

        $builder = QueryBuilder::for(Enrollment::class)
            ->whereIn('course_id', $courseIds);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts([
                'enrolled_at', 
                'completed_at', 
                'created_at',
                AllowedSort::callback('priority', function ($query, $descending) {
                    $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                          ->orderBy('created_at', 'desc');
                }),
            ])
            ->defaultSort('priority')
            ->paginate($perPage);
    }

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = request()->input('search');

        $builder = QueryBuilder::for(Enrollment::class)
            ->where('user_id', $userId);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts(['enrolled_at', 'completed_at', 'created_at'])
            ->defaultSort('-enrolled_at')
            ->paginate($perPage);
    }

    public function paginateAll(int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = request()->input('search');

        $builder = QueryBuilder::for(Enrollment::class);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                AllowedSort::callback('priority', function ($query, $descending) {
                    $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                          ->orderBy('created_at', 'desc');
                }),
            ])
            ->defaultSort('priority')
            ->paginate($perPage);
    }

    /**
     * Get managed enrollments for a user (courses they manage)
     */
    public function getManagedEnrollments(User $user, int $perPage = 15, ?string $courseSlug = null): array
    {
        $courses = Course::query()
            ->select(['id', 'slug', 'title'])
            ->where(function ($query) use ($user) {
                $query
                    ->where('instructor_id', $user->id)
                    ->orWhereHas('admins', function ($adminQuery) use ($user) {
                        $adminQuery->where('user_id', $user->id);
                    });
            })
            ->get();

        $courseIds = $courses->pluck('id')->all();

        if ($courseSlug) {
            $course = $courses->firstWhere('slug', $courseSlug);
            if (! $course) {
                return [
                    'found' => false,
                    'paginator' => null,
                ];
            }
            $paginator = $this->paginateByCourse($course->id, $perPage);
        } else {
            $paginator = $this->paginateByCourseIds($courseIds, $perPage);
        }

        return [
            'found' => true,
            'paginator' => $paginator,
        ];
    }

    public function findById(int $id): ?Enrollment
    {
        return $this->repository->findById($id);
    }

    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment
    {
        return $this->repository->findByCourseAndUser($courseId, $userId);
    }

    /**
     * @return array{enrollment: Enrollment, status: string, message: string}
     *
     * @throws BusinessException
     */
    public function enroll(Course $course, User $user, CreateEnrollmentDTO $dto): array
    {
        return DB::transaction(function () use ($course, $user, $dto) {
            $existing = $this->repository->findByCourseAndUser($course->id, $user->id);

            if ($existing) {
                throw new BusinessException(
                    __('messages.enrollments.already_enrolled_or_cancelled'),
                    ['course' => __('messages.enrollments.already_enrolled_or_cancelled')]
                );
            }

            $enrollment = new Enrollment([
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);

            [$status, $message] = $this->determineStatusAndMessage($course, $dto);

            $enrollment->status = EnrollmentStatus::from($status);
            $enrollment->enrolled_at = Carbon::now();

            if ($status !== EnrollmentStatus::Completed->value) {
                $enrollment->completed_at = null;
            }

            // Save without syncing to Meilisearch
            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());
            
            // Load relations instead of fresh() to avoid extra queries
            $enrollment->load(['course:id,title,slug,code', 'user:id,name,email']);

            // Dispatch event and send emails asynchronously after response
            $enrollmentId = $enrollment->id;
            $courseId = $course->id;
            $userId = $user->id;
            $enrollStatus = $status;
            
            dispatch(function () use ($enrollmentId, $courseId, $userId, $enrollStatus) {
                $enrollment = Enrollment::with(['course:id,title,slug,code', 'user:id,name,email'])->find($enrollmentId);
                if ($enrollment) {
                    EnrollmentCreated::dispatch($enrollment);
                    $this->sendEnrollmentEmails($enrollment, $enrollment->course, $enrollment->user, $enrollStatus);
                }
            })->afterResponse();

            return [
                'enrollment' => $enrollment,
                'status' => $status,
                'message' => $message,
            ];
        });
    }

    /**
     * @throws BusinessException
     */
    public function cancel(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_cancel_pending'),
                    ['enrollment' => __('messages.enrollments.cannot_cancel_pending')]
                );
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;
            
            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
        });
    }

    /**
     * @throws BusinessException
     */
    public function withdraw(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Active) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_withdraw_active'),
                    ['enrollment' => __('messages.enrollments.cannot_withdraw_active')]
                );
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;
            
            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
        });
    }

    /**
     * @throws BusinessException
     */
    public function approve(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_approve_pending'),
                    ['enrollment' => __('messages.enrollments.cannot_approve_pending')]
                );
            }

            $enrollment->status = EnrollmentStatus::Active;
            $enrollment->enrolled_at = Carbon::now();
            $enrollment->completed_at = null;
            
            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
            $course = $freshEnrollment->course;
            $student = $freshEnrollment->user;

            if ($student && $course) {
                $courseUrl = $this->getCourseUrl($course);
                Mail::to($student->email)->send(new StudentEnrollmentApprovedMail($student, $course, $courseUrl));
            }

            return $freshEnrollment;
        });
    }

    /**
     * @throws BusinessException
     */
    public function decline(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_decline_pending'),
                    ['enrollment' => __('messages.enrollments.cannot_decline_pending')]
                );
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;
            
            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
            $course = $freshEnrollment->course;
            $student = $freshEnrollment->user;

            if ($student && $course) {
                Mail::to($student->email)->send(new StudentEnrollmentDeclinedMail($student, $course));
            }

            return $freshEnrollment;
        });
    }

    /**
     * @throws BusinessException
     */
    public function remove(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if (! collect([EnrollmentStatus::Active, EnrollmentStatus::Pending])->contains($enrollment->status)) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_remove_active_pending'),
                    ['enrollment' => __('messages.enrollments.cannot_remove_active_pending')]
                );
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;
            
            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function determineStatusAndMessage(Course $course, CreateEnrollmentDTO $dto): array
    {
        $type = $course->enrollment_type;

        $typeValue = $type instanceof \Modules\Schemes\Enums\EnrollmentType ? $type->value : ($type ?? 'auto_accept');

        return match ($typeValue) {
            'auto_accept' => ['active', __('messages.enrollments.auto_accept_success')],
            'key_based' => $this->handleKeyBasedEnrollment($course, $dto),
            'approval' => ['pending', __('messages.enrollments.approval_sent')],
            default => ['active', __('messages.enrollments.enrolled_success')],
        };
    }

    /**
     * @return array{0: string, 1: string}
     *
     * @throws BusinessException
     */
    private function handleKeyBasedEnrollment(Course $course, CreateEnrollmentDTO $dto): array
    {
        $providedKey = trim((string) ($dto->enrollmentKey ?? ''));

        if ($providedKey === '') {
            throw new BusinessException(
                __('messages.enrollments.key_required'),
                ['enrollment_key' => __('messages.enrollments.key_required')]
            );
        }

        if (empty($course->enrollment_key_hash) || ! $this->keyHasher->verify($providedKey, $course->enrollment_key_hash)) {
            throw new BusinessException(
                __('messages.enrollments.key_invalid'),
                ['enrollment_key' => __('messages.enrollments.key_invalid')]
            );
        }

        return ['active', __('messages.enrollments.key_based_success')];
    }

    private function sendEnrollmentEmails(Enrollment $enrollment, Course $course, User $student, string $status): void
    {
        if ($status === EnrollmentStatus::Active->value) {
            $courseUrl = $this->getCourseUrl($course);
            Mail::to($student->email)->send(new StudentEnrollmentActiveMail($student, $course, $courseUrl));
        } elseif ($status === EnrollmentStatus::Pending->value) {
            Mail::to($student->email)->send(new StudentEnrollmentPendingMail($student, $course));
        }

        $this->notifyCourseManagers($enrollment, $course, $student);
    }

    private function notifyCourseManagers(Enrollment $enrollment, Course $course, User $student): void
    {
        $managers = $this->getCourseManagers($course);
        $enrollmentsUrl = $this->getEnrollmentsUrl($course);

        foreach ($managers as $manager) {
            if ($manager && $manager->email) {
                Mail::to($manager->email)->send(
                    new AdminEnrollmentNotificationMail($manager, $student, $course, $enrollment, $enrollmentsUrl)
                );
            }
        }
    }

    /**
     * @return array<int, User>
     */
    private function getCourseManagers(Course $course): array
    {
        $managers = [];
        $managerIds = [];

        $course = $course->fresh(['instructor', 'admins']);

        if ($course->instructor_id && $course->instructor) {
            $instructor = $course->instructor;
            $managers[] = $instructor;
            $managerIds[] = $instructor->id;
        }

        foreach ($course->admins as $admin) {
            if ($admin && ! collect($managerIds)->contains($admin->id)) {
                $managers[] = $admin;
                $managerIds[] = $admin->id;
            }
        }

        return $managers;
    }

    private function getCourseUrl(Course $course): string
    {
        return UrlHelper::getCourseUrl($course);
    }

    private function getEnrollmentsUrl(Course $course): string
    {
        return UrlHelper::getEnrollmentsUrl($course);
    }

    public function isUserEnrolledInCourse(int $userId, int $courseId): bool
    {
        return $this->repository->hasActiveEnrollment($userId, $courseId);
    }

    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment
    {
        return $this->repository->getActiveEnrollment($userId, $courseId);
    }

    /**
     * List enrollments based on user role (context-aware)
     */
    public function listEnrollments(User $user, int $perPage, ?string $courseSlug): LengthAwarePaginator
    {
        if ($user->hasRole('Superadmin')) {
            return $this->paginateAll($perPage);
        }

        if ($user->hasRole('Admin') || $user->hasRole('Instructor')) {
            $result = $this->getManagedEnrollments($user, $perPage, $courseSlug);
            
            if (! $result['found']) {
                throw new BusinessException(
                    __('messages.enrollments.course_not_managed'),
                    []
                );
            }

            return $result['paginator'];
        }

        return $this->paginateByUser($user->id, $perPage);
    }

    /**
     * Cancel enrollment with find logic
     */
    public function cancelEnrollment(Course $course, User $user, ?int $targetUserId): Enrollment
    {
        $userId = $targetUserId ?? $user->id;
        $enrollment = $this->findByCourseAndUser($course->id, $userId);

        if (!$enrollment) {
            throw new BusinessException(
                __('messages.enrollments.request_not_found'),
                []
            );
        }

        return $enrollment;
    }

    /**
     * Withdraw from enrollment with find logic
     */
    public function withdrawEnrollment(Course $course, User $user, ?int $targetUserId): Enrollment
    {
        $userId = $targetUserId ?? $user->id;
        $enrollment = $this->findByCourseAndUser($course->id, $userId);

        if (!$enrollment) {
            throw new BusinessException(
                __('messages.enrollments.not_found'),
                []
            );
        }

        return $enrollment;
    }

    /**
     * Get enrollment status with find logic
     */
    public function getEnrollmentStatus(Course $course, User $user, ?int $targetUserId): array
    {
        $userId = $targetUserId ?? $user->id;
        $enrollment = $this->findByCourseAndUser($course->id, $userId);

        return [
            'found' => (bool) $enrollment,
            'enrollment' => $enrollment,
        ];
    }
}
