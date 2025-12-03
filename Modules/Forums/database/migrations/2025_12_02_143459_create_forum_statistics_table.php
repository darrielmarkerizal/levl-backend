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
        Schema::create('forum_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('threads_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('avg_response_time_minutes')->nullable();
            $table->decimal('response_rate', 5, 2)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            // Unique constraint: one stat record per scheme/user/period
            $table->unique(['scheme_id', 'user_id', 'period_start', 'period_end'], 'unique_stat');

            // Index for querying statistics
            $table->index(['scheme_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_statistics');
    }
};
