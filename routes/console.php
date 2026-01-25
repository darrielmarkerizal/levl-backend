<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Content Publishing
Schedule::job(new \Modules\Content\Jobs\PublishScheduledContent)->everyFiveMinutes();

// Schedule Account Cleanup (Daily)
Schedule::command('auth:cleanup-deleted-accounts')->daily();

// Housekeeping: mark missing submissions shortly after deadlines
Schedule::job(new \Modules\Learning\Jobs\MarkMissingSubmissionsJob)->everyMinute();
