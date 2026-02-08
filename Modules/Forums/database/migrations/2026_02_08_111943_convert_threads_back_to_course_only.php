<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->after('id')->nullable();
        });

        DB::statement("
            UPDATE threads 
            SET course_id = forumable_id 
            WHERE forumable_type = 'Modules\\\\Schemes\\\\Models\\\\Course'
        ");

        Schema::table('threads', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
        });

        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex(['forumable_type', 'forumable_id', 'is_pinned']);
            $table->dropIndex(['forumable_type', 'forumable_id', 'last_activity_at']);
            $table->dropIndex(['forumable_type', 'forumable_id']);
        });

        Schema::table('threads', function (Blueprint $table) {
            $table->dropColumn(['forumable_type', 'forumable_id']);
        });

        Schema::table('threads', function (Blueprint $table) {
            $table->index(['course_id', 'last_activity_at']);
            $table->index(['course_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'is_pinned']);
            $table->dropIndex(['course_id', 'last_activity_at']);
        });

        Schema::table('threads', function (Blueprint $table) {
            $table->string('forumable_type')->after('id')->default('Modules\\Schemes\\Models\\Course');
            $table->unsignedBigInteger('forumable_id')->after('forumable_type')->default(0);
        });

        DB::statement("
            UPDATE threads 
            SET forumable_id = course_id,
                forumable_type = 'Modules\\\\Schemes\\\\Models\\\\Course'
        ");

        Schema::table('threads', function (Blueprint $table) {
            $table->index(['forumable_type', 'forumable_id']);
            $table->index(['forumable_type', 'forumable_id', 'last_activity_at']);
            $table->index(['forumable_type', 'forumable_id', 'is_pinned']);
        });

        Schema::table('threads', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });
    }
};
