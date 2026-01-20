<?php

namespace Modules\Enrollments\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Progress initialization is now handled via Model::created() boot method
        // which dispatches InitializeEnrollmentProgressJob asynchronously
        // \Modules\Enrollments\Events\EnrollmentCreated::class => [
        //     \Modules\Enrollments\Listeners\InitializeProgressForEnrollment::class,
        // ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
