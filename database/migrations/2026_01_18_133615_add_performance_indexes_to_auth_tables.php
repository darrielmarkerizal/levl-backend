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
        // Users table - Login, filtering, sorting
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
            $table->index('username', 'idx_users_username');
            $table->index('status', 'idx_users_status');
            $table->index('created_at', 'idx_users_created_at');
            $table->index(['status', 'created_at'], 'idx_users_status_created');
            $table->index(['email', 'status'], 'idx_users_email_status');
        });

        // JWT Refresh Tokens - Token lookup, cleanup
        Schema::table('jwt_refresh_tokens', function (Blueprint $table) {
            $table->index('user_id', 'idx_jwt_user_id');
            $table->index('device_id', 'idx_jwt_device_id');
            $table->index('expires_at', 'idx_jwt_expires_at');
            $table->index(['user_id', 'device_id'], 'idx_jwt_user_device');
            $table->index(['user_id', 'expires_at'], 'idx_jwt_user_expires');
        });

        // User Activities - Activity history
        Schema::table('user_activities', function (Blueprint $table) {
            $table->index('user_id', 'idx_activities_user_id');
            $table->index('created_at', 'idx_activities_created_at');
            $table->index(['user_id', 'created_at'], 'idx_activities_user_created');
        });

        // Model Has Roles - Role assignments
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->index('model_id', 'idx_model_roles_model_id');
            $table->index('role_id', 'idx_model_roles_role_id');
            $table->index(['model_id', 'role_id'], 'idx_model_roles_model_role');
        });

        // Model Has Permissions - Permission assignments
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->index('model_id', 'idx_model_perms_model_id');
            $table->index('permission_id', 'idx_model_perms_permission_id');
            $table->index(['model_id', 'permission_id'], 'idx_model_perms_model_perm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_username');
            $table->dropIndex('idx_users_status');
            $table->dropIndex('idx_users_created_at');
            $table->dropIndex('idx_users_status_created');
            $table->dropIndex('idx_users_email_status');
        });

        Schema::table('jwt_refresh_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_jwt_user_id');
            $table->dropIndex('idx_jwt_device_id');
            $table->dropIndex('idx_jwt_expires_at');
            $table->dropIndex('idx_jwt_user_device');
            $table->dropIndex('idx_jwt_user_expires');
        });

        Schema::table('user_activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_user_id');
            $table->dropIndex('idx_activities_created_at');
            $table->dropIndex('idx_activities_user_created');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('idx_model_roles_model_id');
            $table->dropIndex('idx_model_roles_role_id');
            $table->dropIndex('idx_model_roles_model_role');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_model_perms_model_id');
            $table->dropIndex('idx_model_perms_permission_id');
            $table->dropIndex('idx_model_perms_model_perm');
        });
    }
};
