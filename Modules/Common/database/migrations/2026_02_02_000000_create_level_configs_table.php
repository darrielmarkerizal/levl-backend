<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->unique();
            $table->string('name');
            $table->integer('xp_required')->default(0);
            $table->json('rewards')->nullable();
            $table->timestamps();

            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_configs');
    }
};
