<?php

declare(strict_types=1);

namespace Modules\Enrollments\Enums;

enum ProgressStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => __('enums.progress_status.not_started'),
            self::InProgress => __('enums.progress_status.in_progress'),
            self::Completed => __('enums.progress_status.completed'),
        };
    }
}
