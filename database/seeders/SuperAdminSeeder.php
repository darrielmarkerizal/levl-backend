<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('Superadmin', 'api');
        Role::findOrCreate('Admin', 'api');
        Role::findOrCreate('Instructor', 'api');
        Role::findOrCreate('Student', 'api');

        $email = config('app.superadmin.email');
        $name = config('app.superadmin.name');
        $username = config('app.superadmin.username');
        $password = config('app.superadmin.password');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'password' => Hash::make($password),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if ($user->hasRole('super-admin')) {
            $user->removeRole('super-admin');
        }

        if (! $user->hasRole('Superadmin')) {
            $user->assignRole('Superadmin');
        }
    }
}
