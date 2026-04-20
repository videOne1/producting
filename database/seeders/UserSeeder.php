<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'is_active' => (bool) random_int(0, 1),
                'password' => 'password',
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified_at' => now(),
                'is_active' => (bool) random_int(0, 1),
                'password' => 'password',
            ],
        ];

        for ($i = 1; $i <= 20; $i++) {
            $users[] = [
                'name' => sprintf('Demo User %02d', $i),
                'email' => sprintf('user%02d@example.com', $i),
                'email_verified_at' => now(),
                'is_active' => (bool) random_int(0, 1),
                'password' => 'password',
            ];
        }

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                $user,
            );
        }
    }
}
