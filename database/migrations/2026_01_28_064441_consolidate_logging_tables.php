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
        // 1. Migrate user_activities
        // Source: user_id, activity_type, activity_data, related_type, related_id, created_at
        // Target: causer_id, event, properties, subject_type, subject_id, created_at
        DB::table('user_activities')->orderBy('id')->chunk(100, function ($rows) {
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'log_name' => 'user_activity',
                    'description' => $row->activity_type,
                    'event' => $row->activity_type,
                    'subject_type' => $row->related_type,
                    'subject_id' => $row->related_id,
                    'causer_type' => 'Modules\Auth\Models\User',
                    'causer_id' => $row->user_id,
                    'properties' => $row->activity_data, // Already JSON string or needs encoding? DB::table returns object, json columns are strings usually unless cast.
                    // Warning: activity_log.properties is JSON column. If row->activity_data is string, we might need to verify.
                    // Usually raw DB selection returns string for JSON columns in array fetch mode, but let's assume raw string insert is safe or we decode-encode if needed.
                    // Laravel DB::table insert takes array. 
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ];
            }
            if (!empty($data)) {
                DB::table('activity_log')->insert($data);
            }
        });

        // 2. Migrate audit_logs (Common)
        // Source: action, subject_type, subject_id, actor_type, actor_id, context, created_at
        DB::table('audit_logs')->orderBy('id')->chunk(100, function ($rows) {
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'log_name' => 'audit_log',
                    'description' => $row->action,
                    'event' => $row->action,
                    'subject_type' => $row->subject_type,
                    'subject_id' => $row->subject_id,
                    'causer_type' => $row->actor_type,
                    'causer_id' => $row->actor_id,
                    'properties' => $row->context,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ];
            }
            if (!empty($data)) {
                DB::table('activity_log')->insert($data);
            }
        });

        // 3. Migrate profile_audit_logs (Auth)
        // Source: user_id (Modified User), admin_id (Actor), action, changes, created_at
        DB::table('profile_audit_logs')->orderBy('id')->chunk(100, function ($rows) {
            $data = [];
            foreach ($rows as $row) {
                $props = [];
                if (!empty($row->changes)) {
                     // Check if 'changes' is already a string (it usually is from DB::table)
                    $decoded = is_string($row->changes) ? json_decode($row->changes, true) : $row->changes;
                    $props = ['attributes' => $decoded];
                }

                $data[] = [
                    'log_name' => 'profile_audit',
                    'description' => $row->action,
                    'event' => $row->action,
                    'subject_type' => 'Modules\Auth\Models\User',
                    'subject_id' => $row->user_id,
                    'causer_type' => 'Modules\Auth\Models\User',
                    'causer_id' => $row->admin_id,
                    'properties' => json_encode($props),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ];
            }
            if (!empty($data)) {
                DB::table('activity_log')->insert($data);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated logs
        DB::table('activity_log')->whereIn('log_name', ['user_activity', 'audit_log', 'profile_audit'])->delete();
    }
};
