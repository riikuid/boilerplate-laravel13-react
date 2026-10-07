<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([PermissionSeeder::class, RoleSeeder::class]);

        // Production has no default account: create one with `php artisan app:create-admin`.
        if (app()->environment('local')) {
            $admin = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                ['name' => 'Super Admin', 'password' => 'password'],
            );

            $admin->forceFill(['email_verified_at' => now()])->save();
            $admin->assignRole(RoleName::SuperAdmin->value);
        }
    }
}
