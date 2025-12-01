<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'system', 'assignment', 'assessment', 'grading', 'gamification', 'news', 'custom',
                'course_completed', 'enrollment',
            ])->default('system');
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->enum('channel', ['in_app', 'email', 'push'])->default('in_app');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->boolean('is_broadcast')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
