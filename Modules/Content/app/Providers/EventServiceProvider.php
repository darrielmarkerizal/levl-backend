<?php

namespace Modules\Content\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Content\Events\AnnouncementPublished::class => [
            \Modules\Content\Listeners\NotifyTargetAudienceOnAnnouncementPublished::class,
        ],
        \Modules\Content\Events\NewsPublished::class => [
            \Modules\Content\Listeners\NotifyUsersOnNewsPublished::class,
        ],
        \Modules\Content\Events\ContentSubmitted::class => [
            \Modules\Content\Listeners\NotifyReviewersOnContentSubmitted::class,
        ],
        \Modules\Content\Events\ContentApproved::class => [
            \Modules\Content\Listeners\NotifyAuthorOnContentApproved::class,
        ],
        \Modules\Content\Events\ContentRejected::class => [
            \Modules\Content\Listeners\NotifyAuthorOnContentRejected::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
