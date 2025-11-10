<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->integer('current_level')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'course_id'], 'levels_user_course_unique');
            $table->index(['user_id', 'course_id'], 'levels_user_course_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
