<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Content Publishing
Schedule::job(new \Modules\Content\Jobs\PublishScheduledContent)->everyFiveMinutes();

// Schedule Assessment Reminders (run every hour to check for upcoming assessments)
Schedule::job(new \Modules\Assessments\Jobs\SendAssessmentReminders)->hourly();
