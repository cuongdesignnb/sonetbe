<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email} {password} {--name=Admin}';

    protected $description = 'Create or update an admin user (sets role=admin and verifies email)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) $this->argument('password');
        $name = (string) $this->option('name');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $this->info("Created admin user: {$user->email}");
            return self::SUCCESS;
        }

        $user->update([
            'name' => $name ?: $user->name,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => $user->email_verified_at ?: now(),
            'is_active' => true,
        ]);

        $this->info("Updated admin user: {$user->email}");
        return self::SUCCESS;
    }
}
