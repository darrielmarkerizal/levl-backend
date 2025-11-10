<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('rank')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'user_id'], 'leaderboards_course_user_unique');
            $table->index(['user_id', 'course_id'], 'leaderboards_user_course_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};
