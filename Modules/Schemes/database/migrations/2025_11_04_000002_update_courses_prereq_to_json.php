<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (! Schema::hasColumn('courses', 'prereq_json')) {
                    $table->json('prereq_json')->nullable()->after('outcomes_json');
                }
                if (Schema::hasColumn('courses', 'prereq_text')) {
                    $table->dropColumn('prereq_text');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (! Schema::hasColumn('courses', 'prereq_text')) {
                    $table->text('prereq_text')->nullable()->after('outcomes_json');
                }
                if (Schema::hasColumn('courses', 'prereq_json')) {
                    $table->dropColumn('prereq_json');
                }
            });
        }
    }
};
