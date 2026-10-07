<?php

namespace Database\Seeders;

use App\Services\PermissionSyncService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(PermissionSyncService $permissions): void
    {
        $permissions->sync();
    }
}
