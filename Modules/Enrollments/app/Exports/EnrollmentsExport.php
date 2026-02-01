<?php

declare(strict_types=1);

namespace Modules\Enrollments\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class EnrollmentsExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private readonly Builder $query
    ) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Student Name',
            'Email',
            'Status',
            'Progress %',
            'Enrolled At',
            'Completed At',
        ];
    }

    public function map($enrollment): array
    {
        $progress = $enrollment->courseProgress;

        return [
            $enrollment->id,
            $enrollment->user?->name ?? 'N/A',
            $enrollment->user?->email ?? 'N/A',
            $enrollment->status?->value,
            $progress?->progress_percent ?? 0,
            $enrollment->enrolled_at?->toDateTimeString(),
            $enrollment->completed_at?->toDateTimeString() ?? 'N/A',
        ];
    }

    public function title(): string
    {
        return 'Enrollments';
    }
}
