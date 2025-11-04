<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_tags')) {
            Schema::drop('course_tags');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('course_tags')) {
            Schema::create('course_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
                $table->string('tag', 100);
                $table->timestamps();
                $table->index(['course_id', 'tag']);
            });
        }
    }
};
