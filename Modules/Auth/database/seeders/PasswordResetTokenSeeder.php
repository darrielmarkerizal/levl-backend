<?php
declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\PasswordResetToken;
use Modules\Auth\Models\User;

class PasswordResetTokenSeeder extends Seeder
{
    public function run(): void
    {
        echo "Creating password reset tokens...\n";

        $count = 0;

        $expiredUsers = User::inRandomOrder()->limit(5)->get();
        $expiredEmails = $expiredUsers->pluck('email')->all();
        foreach ($expiredUsers as $user) {
            PasswordResetToken::updateOrCreate(
                ['email' => $user->email],
                [
                    'token' => hash('sha256', \Illuminate\Support\Str::random(32)),
                    'created_at' => now()->subHours(2),
                ]
            );
            $count++;
        }

        $validUsers = User::where('status', 'active')
            ->whereNotIn('email', $expiredEmails)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        foreach ($validUsers as $user) {
            PasswordResetToken::updateOrCreate(
                ['email' => $user->email],
                [
                    'token' => hash('sha256', \Illuminate\Support\Str::random(32)),
                    'created_at' => now()->subMinutes(5),
                ]
            );
            $count++;
        }

        echo "✅ Created $count password reset tokens\n";
    }
}
