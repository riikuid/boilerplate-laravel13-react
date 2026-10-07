<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Exceptions\ProtectedRecordException;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Accounts created by an admin are marked verified: the admin vouches for the address.
     *
     * @param  array<string, mixed>  $data  name, email, password, roles?
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            /** @var list<string> $roles */
            $roles = $data['roles'] ?? [];
            $this->assertCanAssignRoles($roles);

            $user = new User(Arr::only($data, ['name', 'email', 'password']));
            $user->email_verified_at = now();
            $user->save();

            $this->syncRoles($user, $roles);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data  name, email, password?, roles?
     */
    public function updateUser(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data): User {
            $user = User::query()->lockForUpdate()->findOrFail($id);
            $this->assertCanManage($user);

            /** @var list<string>|null $roles */
            $roles = $data['roles'] ?? null;

            if ($roles !== null) {
                $this->assertCanAssignRoles($roles);

                if ($user->isSuperAdmin() && ! in_array(RoleName::SuperAdmin->value, $roles, true)) {
                    $this->assertNotLastSuperAdmin();
                }
            }

            $user->fill(Arr::only($data, ['name', 'email']));

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }

            $user->save();

            if ($roles !== null) {
                $this->syncRoles($user, $roles);
            }

            return $user;
        });
    }

    public function deleteUser(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $user = User::query()->lockForUpdate()->findOrFail($id);

            if ($user->id === Auth::id()) {
                throw new ProtectedRecordException('Anda tidak dapat menghapus akun sendiri.', 'cannot_delete_self');
            }

            $this->assertCanManage($user);

            if ($user->isSuperAdmin()) {
                $this->assertNotLastSuperAdmin();
            }

            $user->delete();
        });
    }

    /** @param  list<string>  $roles */
    private function syncRoles(User $user, array $roles): void
    {
        $old = $user->getRoleNames()->sort()->values()->all();

        $user->syncRoles($roles);

        $new = $user->getRoleNames()->sort()->values()->all();

        if ($old !== $new) {
            $this->audit->record(AuditEvent::UserRolesChanged, $user, ['roles' => $old], ['roles' => $new]);
        }
    }

    /**
     * Only a super admin may hand out the super-admin role.
     *
     * @param  list<string>  $roles
     */
    private function assertCanAssignRoles(array $roles): void
    {
        if (in_array(RoleName::SuperAdmin->value, $roles, true) && ! $this->actorIsSuperAdmin()) {
            throw new ProtectedRecordException('Hanya super admin yang dapat memberikan peran super-admin.');
        }
    }

    /** A non-super-admin could otherwise take over a super admin by resetting their password. */
    private function assertCanManage(User $target): void
    {
        if ($target->isSuperAdmin() && ! $this->actorIsSuperAdmin()) {
            throw new ProtectedRecordException('Hanya super admin yang dapat mengubah akun super admin.');
        }
    }

    private function assertNotLastSuperAdmin(): void
    {
        // lockForUpdate cannot be combined with count(); lock the rows and count them in PHP.
        $superAdminIds = User::role(RoleName::SuperAdmin->value)->lockForUpdate()->pluck('users.id')->all();

        if (count($superAdminIds) <= 1) {
            throw new ProtectedRecordException('Super admin terakhir tidak dapat dihapus atau dicabut perannya.', 'last_super_admin');
        }
    }

    private function actorIsSuperAdmin(): bool
    {
        $actor = Auth::user();

        return $actor instanceof User && $actor->isSuperAdmin();
    }
}
