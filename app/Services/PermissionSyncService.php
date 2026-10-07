<?php

namespace App\Services;

use App\Permissions\PermissionRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSyncService
{
    /**
     * Make the permissions table match PermissionRegistry. Idempotent.
     * Permissions that were removed from code are only deleted with $prune.
     *
     * @return array{created: int, pruned: int}
     */
    public function sync(bool $prune = false): array
    {
        $wanted = PermissionRegistry::all();
        $existing = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

        $missing = array_diff($wanted, $existing);

        foreach ($missing as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        $pruned = 0;

        if ($prune) {
            $pruned = Permission::query()
                ->where('guard_name', 'web')
                ->whereNotIn('name', $wanted)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ['created' => count($missing), 'pruned' => $pruned];
    }
}
