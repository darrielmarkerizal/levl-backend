<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('avatar_path');
            }
            if (! Schema::hasColumn('users', 'account_status')) {
                $table->enum('account_status', ['active', 'suspended', 'deleted'])->default('active')->after('status');
            }
            if (! Schema::hasColumn('users', 'last_profile_update')) {
                $table->timestamp('last_profile_update')->nullable()->after('account_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['bio', 'phone', 'account_status', 'last_profile_update'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
