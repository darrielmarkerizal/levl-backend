<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('source_type', ['lesson', 'assignment', 'attempt', 'system'])->default('system');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->integer('points')->default(0);
            $table->enum('reason', ['completion', 'score', 'bonus', 'penalty'])->default('completion');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'source_type', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};
