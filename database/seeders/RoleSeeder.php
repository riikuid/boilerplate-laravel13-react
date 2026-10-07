<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Permissions\PermissionRegistry;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // super-admin bypasses every check through Gate::before, so it holds no permissions itself.
        Role::findOrCreate(RoleName::SuperAdmin->value, 'web');

        // admin starts with every permission; narrow it down per project in the role editor.
        Role::findOrCreate(RoleName::Admin->value, 'web')->syncPermissions(PermissionRegistry::all());
    }
}
