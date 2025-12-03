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
        Schema::create('content_workflow_history', function (Blueprint $table) {
            $table->id();
            $table->string('content_type');
            $table->unsignedBigInteger('content_id');
            $table->string('from_state', 50);
            $table->string('to_state', 50);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['content_type', 'content_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_workflow_history');
    }
};
