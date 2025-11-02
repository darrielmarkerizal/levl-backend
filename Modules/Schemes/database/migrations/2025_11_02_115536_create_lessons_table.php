<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->string('slug', 100)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->longText('markdown_content')->nullable(); 
            $table->enum('content_type', ['markdown', 'video', 'link'])->default('markdown');
            $table->string('content_url')->nullable();
            $table->integer('order')->default(1);
            $table->integer('estimated_duration')->default(0);
            $table->timestamps();

            $table->index(['unit_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
