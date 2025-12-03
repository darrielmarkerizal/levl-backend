<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add workflow statuses to news table
        DB::statement("ALTER TABLE news MODIFY COLUMN status ENUM('draft', 'submitted', 'in_review', 'approved', 'rejected', 'scheduled', 'published', 'archived') DEFAULT 'draft'");

        // Add workflow statuses to announcements table
        DB::statement("ALTER TABLE announcements MODIFY COLUMN status ENUM('draft', 'submitted', 'in_review', 'approved', 'rejected', 'scheduled', 'published', 'archived') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original statuses
        DB::statement("ALTER TABLE news MODIFY COLUMN status ENUM('draft', 'published', 'scheduled') DEFAULT 'draft'");
        DB::statement("ALTER TABLE announcements MODIFY COLUMN status ENUM('draft', 'published', 'scheduled') DEFAULT 'draft'");
    }
};
