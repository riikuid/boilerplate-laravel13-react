<?php

namespace App\Console\Commands;

use App\Services\PermissionSyncService;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync {--prune : Delete permissions that no longer exist in code}';

    protected $description = 'Sync the permissions table with the PermissionRegistry';

    public function handle(PermissionSyncService $permissions): int
    {
        $result = $permissions->sync((bool) $this->option('prune'));

        $this->components->info("Permissions synced: {$result['created']} created, {$result['pruned']} pruned.");

        return self::SUCCESS;
    }
}
