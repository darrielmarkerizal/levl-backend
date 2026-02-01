<?php

declare(strict_types=1);

namespace Modules\Enrollments\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\Enrollments\Events\EnrollmentCreated::class => [
            \Modules\Enrollments\Listeners\InitializeProgressForEnrollment::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
