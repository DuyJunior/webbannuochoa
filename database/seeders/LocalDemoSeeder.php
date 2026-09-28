<?php

namespace Database\Seeders;

use App\Models\Perfume;
use App\Models\User;
use App\Support\DemoMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! DemoMode::enabled()) {
            throw new \RuntimeException('Demo seeder requires local/testing environment and DEMO_MODE=true.');
        }
        // Never overwrite existing catalog, customer accounts or their passwords.
        if (! Perfume::exists()) {
            $this->call(PerfumeCatalogSeeder::class);
            Perfume::query()->update(['stock_5ml' => 12, 'stock_10ml' => 15, 'stock_50ml' => 10]);
        }
        foreach (['admin' => 'admin.demo@example.test', 'user' => 'customer.demo@example.test'] as $role => $email) {
            User::firstOrCreate(['email' => $email], [
                'name' => $role === 'admin' ? 'Quản trị demo' : 'Khách demo',
                'password' => Hash::make('DemoPerfume2026!'), 'role' => $role, 'email_verified_at' => now(),
            ]);
        }
    }
}
