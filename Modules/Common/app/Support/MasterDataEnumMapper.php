<?php

declare(strict_types=1);

namespace Modules\Common\Support;

use Modules\Auth\Enums\UserStatus;
use Modules\Common\Enums\CategoryStatus;
use Modules\Common\Enums\SettingType;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Enums\Priority;
use Modules\Content\Enums\TargetType;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Enums\ProgressStatus;
use Modules\Gamification\Enums\BadgeType;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;
use Modules\Gamification\Enums\ChallengeCriteriaType;
use Modules\Gamification\Enums\ChallengeType;
use Modules\Gamification\Enums\PointReason;
use Modules\Gamification\Enums\PointSourceType;
use Modules\Grading\Enums\GradeStatus;
use Modules\Grading\Enums\SourceType;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\SubmissionStatus;
use Modules\Learning\Enums\SubmissionType;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Enums\NotificationFrequency;
use Modules\Notifications\Enums\NotificationType;
use Modules\Schemes\Enums\ContentType;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\LevelTag;
use Modules\Schemes\Enums\ProgressionMode;
use Spatie\Permission\Models\Role;

class MasterDataEnumMapper
{
    public function getStaticTypes(): array
    {
        return [
            'user-status' => fn () => $this->transformEnum(UserStatus::class),
            'roles' => fn () => $this->getRoles(),
            'course-status' => fn () => $this->transformEnum(CourseStatus::class),
            'course-types' => fn () => $this->transformEnum(CourseType::class),
            'enrollment-types' => fn () => $this->transformEnum(EnrollmentType::class),
            'level-tags' => fn () => $this->transformEnum(LevelTag::class),
            'progression-modes' => fn () => $this->transformEnum(ProgressionMode::class),
            'content-types' => fn () => $this->transformEnum(ContentType::class),
            'enrollment-status' => fn () => $this->transformEnum(EnrollmentStatus::class),
            'progress-status' => fn () => $this->transformEnum(ProgressStatus::class),
            'assignment-status' => fn () => $this->transformEnum(AssignmentStatus::class),
            'submission-status' => fn () => $this->transformEnum(SubmissionStatus::class),
            'submission-types' => fn () => $this->transformEnum(SubmissionType::class),
            'content-status' => fn () => $this->transformEnum(ContentStatus::class),
            'priorities' => fn () => $this->transformEnum(Priority::class),
            'target-types' => fn () => $this->transformEnum(TargetType::class),
            'challenge-types' => fn () => $this->transformEnum(ChallengeType::class),
            'challenge-assignment-status' => fn () => $this->transformEnum(ChallengeAssignmentStatus::class),
            'challenge-criteria-types' => fn () => $this->transformEnum(ChallengeCriteriaType::class),
            'badge-types' => fn () => $this->transformEnum(BadgeType::class),
            'point-source-types' => fn () => $this->transformEnum(PointSourceType::class),
            'point-reasons' => fn () => $this->transformEnum(PointReason::class),
            'notification-types' => fn () => $this->transformEnum(NotificationType::class),
            'notification-channels' => fn () => $this->transformEnum(NotificationChannel::class),
            'notification-frequencies' => fn () => $this->transformEnum(NotificationFrequency::class),
            'grade-status' => fn () => $this->transformEnum(GradeStatus::class),
            'grade-source-types' => fn () => $this->transformEnum(SourceType::class),
            'category-status' => fn () => $this->transformEnum(CategoryStatus::class),
            'setting-types' => fn () => $this->transformEnum(SettingType::class),
        ];
    }

    public function isStaticType(string $type): bool
    {
        return array_key_exists($type, $this->getStaticTypes());
    }

    private function transformEnum(string $enumClass): array
    {
        return array_map(
            fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            $enumClass::cases()
        );
    }

    private function getRoles(): array
    {
        return Role::all()
            ->map(fn ($role) => [
                'value' => $role->name,
                'label' => __('enums.roles.'.strtolower($role->name)),
            ])
            ->toArray();
    }
}
