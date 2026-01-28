<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Announcements
        Schema::table('announcements', function (Blueprint $table) {
            $table->index('course_id');
        });

        // Submissions
        Schema::table('submissions', function (Blueprint $table) {
            $table->index('enrollment_id');
        });

        // Answers
        Schema::table('answers', function (Blueprint $table) {
            $table->index('submission_id');
            $table->index('question_id');
        });

        // Grade Reviews
        Schema::table('grade_reviews', function (Blueprint $table) {
            $table->index('grade_id');
        });

        // User Activities (Polymorphic)
        Schema::table('user_activities', function (Blueprint $table) {
            $table->index(['related_type', 'related_id']);
        });

        // Activity Log (Polymorphic - Causer)
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['causer_type', 'causer_id']);
        });

        // Assignments (Polymorphic)
        Schema::table('assignments', function (Blueprint $table) {
            $table->index(['assignable_type', 'assignable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['enrollment_id']);
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex(['submission_id']);
            $table->dropIndex(['question_id']);
        });

        Schema::table('grade_reviews', function (Blueprint $table) {
            $table->dropIndex(['grade_id']);
        });

        Schema::table('user_activities', function (Blueprint $table) {
            $table->dropIndex(['related_type', 'related_id']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['causer_type', 'causer_id']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['assignable_type', 'assignable_id']);
        });
    }
};
