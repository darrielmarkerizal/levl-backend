<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_challenge_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('challenge_id')->constrained('challenges')->onDelete('cascade');
            $table->date('completed_date');
            $table->unsignedInteger('xp_earned')->default(0);
            $table->json('completion_data')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'challenge_id', 'completed_date'], 'ucc_user_challenge_date_unique');
            
            $table->index(['user_id', 'completed_date']);
            $table->index(['challenge_id', 'completed_date']);
            $table->index('completed_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_challenge_completions');
    }
};
