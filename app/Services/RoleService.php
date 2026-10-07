<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Exceptions\BusinessException;
use App\Exceptions\ProtectedRecordException;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param  array<string, mixed>  $data  name, permissions? */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::query()->create(['name' => $data['name'], 'guard_name' => 'web']);

            /** @var list<string> $permissions */
            $permissions = $data['permissions'] ?? [];
            $this->syncPermissions($role, $permissions);

            return $role;
        });
    }

    /** @param  array<string, mixed>  $data  name, permissions? */
    public function updateRole(int $id, array $data): Role
    {
        return DB::transaction(function () use ($id, $data): Role {
            $role = Role::query()->lockForUpdate()->findOrFail($id);

            if ($role->isSuperAdmin()) {
                throw new ProtectedRecordException('Peran super-admin tidak dapat diubah.');
            }

            if (isset($data['name']) && $data['name'] !== $role->name) {
                if ($role->isSystemRole()) {
                    throw new ProtectedRecordException('Nama peran bawaan sistem tidak dapat diubah.');
                }

                $role->update(['name' => $data['name']]);
            }

            if (array_key_exists('permissions', $data)) {
                /** @var list<string> $permissions */
                $permissions = $data['permissions'] ?? [];
                $this->syncPermissions($role, $permissions);
            }

            return $role;
        });
    }

    public function deleteRole(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $role = Role::query()->lockForUpdate()->findOrFail($id);

            if ($role->isSystemRole()) {
                throw new ProtectedRecordException('Peran bawaan sistem tidak dapat dihapus.');
            }

            $users = $role->users()->count();

            if ($users > 0) {
                throw new BusinessException("Peran masih dipakai oleh {$users} pengguna.", 409, 'role_in_use');
            }

            $role->delete();
        });
    }

    /** @param  list<string>  $permissions */
    private function syncPermissions(Role $role, array $permissions): void
    {
        $old = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->syncPermissions($permissions);

        $new = $role->permissions()->pluck('name')->sort()->values()->all();

        if ($old !== $new) {
            $this->audit->record(AuditEvent::RolePermissionsChanged, $role, ['permissions' => $old], ['permissions' => $new]);
        }
    }
}
