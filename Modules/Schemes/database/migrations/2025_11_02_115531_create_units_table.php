<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('code', 50)->unique();
            $table->string('slug', 100)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->integer('order')->default(1);
            // $table->integer('estimated_duration')->default(0); (Sama menggunakan logic dari jumlah lesson yang ada)
            $table->timestamps();

            $table->index(['course_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
