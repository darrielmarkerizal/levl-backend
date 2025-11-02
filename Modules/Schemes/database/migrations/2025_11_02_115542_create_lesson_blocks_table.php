<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lesson_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->enum('block_type', ['text', 'image', 'file', 'embed'])->default('text');
            $table->longText('content')->nullable();
            $table->string('media_url')->nullable();
            $table->integer('order')->default(1);
            $table->timestamps();

            $table->index(['lesson_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_blocks');
    }
};
