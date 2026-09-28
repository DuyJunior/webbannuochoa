<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) config('seeding.admin.email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Set a valid SEED_ADMIN_EMAIL before running seeders.');
        }
        $existing = User::where('email', $email)->first();
        if ($existing) {
            if ($existing->role !== 'admin') {
                throw new RuntimeException('SEED_ADMIN_EMAIL belongs to a non-admin account. Choose another email.');
            }
            $this->command?->info('Admin already exists; existing account and password kept.');

            return;
        }
        $password = (string) config('seeding.admin.password');
        if (strlen($password) < 12) {
            throw new RuntimeException('Set SEED_ADMIN_PASSWORD to at least 12 characters.');
        }
        $admin = new User;
        $admin->forceFill([
            'name' => config('seeding.admin.name') ?: 'Shop Admin', 'email' => $email,
            'password' => Hash::make($password), 'role' => 'admin', 'email_verified_at' => now(),
        ])->save();
        $this->command?->info('Admin created and verified using deployment configuration.');
    }
}
