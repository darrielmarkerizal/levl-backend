<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Add time limit field for submissions
            $table->integer('time_limit_minutes')
                ->nullable()
                ->after('tolerance_minutes')
                ->comment('Optional time limit per attempt (in minutes)');
        });

        Schema::table('submissions', function (Blueprint $table) {
            // Track when submission was started (when timer begins)
            $table->timestamp('started_at')
                ->nullable()
                ->after('submitted_at')
                ->comment('When the submission attempt was started');

            // Track when time expires if there is a time limit
            $table->timestamp('time_expired_at')
                ->nullable()
                ->after('started_at')
                ->comment('When the submission time limit expired');

            // Track if submission was auto-submitted due to timeout
            $table->boolean('auto_submitted_on_timeout')
                ->default(false)
                ->after('time_expired_at')
                ->comment('Whether submission was auto-submitted when time limit expired');

            // Index for finding expired submissions
            $table->index('time_expired_at', 'idx_submissions_time_expired');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('time_limit_minutes');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_submissions_time_expired');
            $table->dropColumn([
                'started_at',
                'time_expired_at',
                'auto_submitted_on_timeout',
            ]);
        });
    }
};
