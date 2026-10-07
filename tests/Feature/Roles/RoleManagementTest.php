<?php

namespace Tests\Feature\Roles;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Permissions\RolePermission;
use App\Permissions\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_action_is_forbidden_without_its_permission(): void
    {
        $actor = $this->userWithPermissions(RolePermission::Read);
        $role = Role::findOrCreate('staff', 'web');

        $this->actingAs($actor)->get(route('roles.create'))->assertForbidden();
        $this->actingAs($actor)->post(route('roles.store'), ['name' => 'x'])->assertForbidden();
        $this->actingAs($actor)->get(route('roles.edit', $role))->assertForbidden();
        $this->actingAs($actor)->put(route('roles.update', $role), ['name' => 'staff'])->assertForbidden();
        $this->actingAs($actor)->delete(route('roles.destroy', $role))->assertForbidden();
    }

    public function test_store_creates_a_role_with_permissions_and_audits_the_change(): void
    {
        $actor = $this->userWithPermissions(RolePermission::Create);

        $this->actingAs($actor)->post(route('roles.store'), [
            'name' => 'verifikator',
            'permissions' => [UserPermission::Read->value],
        ])->assertRedirect(route('roles.index'));

        $role = Role::findByName('verifikator', 'web');
        $this->assertTrue($role->hasPermissionTo(UserPermission::Read->value));
        $this->assertDatabaseHas('audit_logs', ['event' => 'role.permissions_changed', 'auditable_id' => $role->id, 'actor_id' => $actor->id]);
    }

    public function test_role_names_must_be_slugs_and_unique(): void
    {
        $actor = $this->userWithPermissions(RolePermission::Create);
        Role::findOrCreate('staff', 'web');

        $this->actingAs($actor)->post(route('roles.store'), ['name' => 'Not A Slug'])->assertSessionHasErrors('name');
        $this->actingAs($actor)->post(route('roles.store'), ['name' => 'staff'])->assertSessionHasErrors('name');
    }

    public function test_update_replaces_permissions(): void
    {
        $actor = $this->userWithPermissions(RolePermission::Update);
        $role = Role::findOrCreate('staff', 'web');
        $role->givePermissionTo($this->userWithPermissions(UserPermission::Read)->getAllPermissions());

        $this->actingAs($actor)->put(route('roles.update', $role), [
            'name' => 'staff',
            'permissions' => [UserPermission::Create->value],
        ])->assertRedirect(route('roles.index'));

        $this->assertSame([UserPermission::Create->value], $role->fresh()?->permissions->pluck('name')->all());
    }

    public function test_system_roles_are_protected(): void
    {
        $actor = $this->userWithPermissions(RolePermission::Update, RolePermission::Delete);
        $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');

        $this->actingAs($actor)->put(route('roles.update', $superAdmin), ['name' => 'super-admin', 'permissions' => [UserPermission::Read->value]])
            ->assertInertiaFlash('toast');
        $this->assertCount(0, $superAdmin->fresh()?->permissions ?? []);

        $this->actingAs($actor)->put(route('roles.update', $admin), ['name' => 'renamed']);
        $this->assertSame('admin', $admin->fresh()?->name);

        $this->actingAs($actor)->delete(route('roles.destroy', $admin));
        $this->assertNotNull(Role::find($admin->id));
    }

    public function test_a_role_in_use_cannot_be_deleted(): void
    {
        $actor = $this->userWithPermissions(RolePermission::Delete);
        $role = Role::findOrCreate('staff', 'web');
        User::factory()->create()->assignRole('staff');

        $this->actingAs($actor)->delete(route('roles.destroy', $role))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Peran masih dipakai oleh 1 pengguna.']);

        $this->assertNotNull(Role::find($role->id));
    }

    public function test_an_unused_role_can_be_deleted(): void
    {
        $actor = $this->userWithPermissions(RolePermission::Delete);
        $role = Role::findOrCreate('staff', 'web');

        $this->actingAs($actor)->delete(route('roles.destroy', $role))->assertRedirect(route('roles.index'));

        $this->assertNull(Role::find($role->id));
    }
}
