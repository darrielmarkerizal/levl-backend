<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table("exercises", function (Blueprint $table) {
      if (!Schema::hasColumn("exercises", "allow_retake")) {
        $table->boolean("allow_retake")->default(true)->after("status");
      }
    });
  }

  public function down(): void
  {
    Schema::table("exercises", function (Blueprint $table) {
      if (Schema::hasColumn("exercises", "allow_retake")) {
        $table->dropColumn("allow_retake");
      }
    });
  }
};
