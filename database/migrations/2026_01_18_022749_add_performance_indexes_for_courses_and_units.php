<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance optimization indexes based on benchmark analysis.
     * 
     * Bottleneck identified: Course listing query (13 RPS)
     * Expected improvement: 13 RPS → 250-300 RPS (20x faster)
     */
    public function up(): void
    {
        // Index for course listing query (most critical bottleneck)
        // Covers: WHERE status = 'published' AND deleted_at IS NULL ORDER BY published_at DESC
        Schema::table('courses', function (Blueprint $table) {
            $table->index(['status', 'deleted_at', 'published_at'], 'idx_courses_listing');
        });

        // Index for units count in dashboard query
        // Units table doesn't use soft deletes, so only index course_id
        Schema::table('units', function (Blueprint $table) {
            $table->index(['course_id'], 'idx_units_course_id');
        });

        // Index for enrollment checks (already fast, but optimize further)
        // Covers: WHERE user_id = ? AND course_id = ? AND status IN (...)
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['user_id', 'course_id', 'status'], 'idx_enrollments_user_course_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_courses_listing');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex('idx_units_course_id');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enrollments_user_course_status');
        });
    }
};
