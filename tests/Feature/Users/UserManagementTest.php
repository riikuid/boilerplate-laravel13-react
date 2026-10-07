<?php

namespace Tests\Feature\Users;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Permissions\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Budi Santoso',
            'email' => 'Budi@Example.com ',
            'password' => 'password-123',
            'roles' => [],
            ...$overrides,
        ];
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_every_action_is_forbidden_without_its_permission(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Read);
        $target = User::factory()->create();

        $this->actingAs($actor)->get(route('users.create'))->assertForbidden();
        $this->actingAs($actor)->post(route('users.store'), $this->payload())->assertForbidden();
        $this->actingAs($actor)->get(route('users.edit', $target))->assertForbidden();
        $this->actingAs($actor)->put(route('users.update', $target), $this->payload())->assertForbidden();
        $this->actingAs($actor)->delete(route('users.destroy', $target))->assertForbidden();

        $nobody = User::factory()->create();
        $this->actingAs($nobody)->get(route('users.index'))->assertForbidden();
    }

    public function test_index_lists_users_with_search(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Read);
        User::factory()->create(['name' => 'Alpha Person', 'email' => 'alpha@example.com']);
        User::factory()->create(['name' => 'Beta Person', 'email' => 'beta@example.com']);

        $this->actingAs($actor)->get(route('users.index', ['search' => 'alpha']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/index')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'alpha@example.com')
                ->where('users.filters.search', 'alpha'));
    }

    public function test_index_ignores_unknown_sort_columns_and_page_sizes(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Read);

        $this->actingAs($actor)->get(route('users.index', ['sort' => 'password', 'per_page' => 100000]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.filters.sort', 'created_at')
                ->where('users.meta.per_page', 15));
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Read);
        User::factory()->create(['name' => 'Plain Name']);

        $this->actingAs($actor)->get(route('users.index', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
    }

    public function test_store_creates_a_verified_stamped_user_with_roles(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Create);
        Role::findOrCreate('staff', 'web');

        $this->actingAs($actor)
            ->post(route('users.store'), $this->payload(['roles' => ['staff']]))
            ->assertRedirect(route('users.index'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil ditambahkan.']);

        $user = User::where('email', 'budi@example.com')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('staff'));
        $this->assertSame($actor->id, $user->created_by);
    }

    public function test_store_validates_input(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Create);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($actor)
            ->post(route('users.store'), $this->payload(['email' => 'taken@example.com', 'roles' => ['missing']]))
            ->assertSessionHasErrors(['email', 'roles.0']);
    }

    public function test_update_keeps_the_password_when_none_is_given(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Update);
        $target = User::factory()->create();
        $hash = $target->password;

        $this->actingAs($actor)
            ->put(route('users.update', $target), $this->payload(['email' => $target->email, 'password' => '']))
            ->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Budi Santoso', $target->name);
        $this->assertSame($hash, $target->password);
        $this->assertSame($actor->id, $target->updated_by);
    }

    public function test_only_a_super_admin_can_hand_out_the_super_admin_role(): void
    {
        Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $actor = $this->userWithPermissions(UserPermission::Create);

        $this->actingAs($actor)
            ->post(route('users.store'), $this->payload(['roles' => [RoleName::SuperAdmin->value]]))
            ->assertRedirect()
            ->assertInertiaFlash('toast');

        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);
    }

    public function test_a_regular_admin_cannot_modify_a_super_admin(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Update, UserPermission::Delete);
        $superAdmin = $this->superAdmin();
        $hash = $superAdmin->password;

        $this->actingAs($actor)
            ->put(route('users.update', $superAdmin), $this->payload(['email' => $superAdmin->email, 'password' => 'new-password-123']))
            ->assertInertiaFlash('toast');
        $this->actingAs($actor)->delete(route('users.destroy', $superAdmin));

        $superAdmin->refresh();
        $this->assertSame($hash, $superAdmin->password);
        $this->assertNull($superAdmin->deleted_at);
    }

    public function test_destroy_soft_deletes_and_stamps_the_actor(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Delete);
        $target = User::factory()->create();

        $this->actingAs($actor)->delete(route('users.destroy', $target))->assertRedirect(route('users.index'));

        $this->assertSoftDeleted($target);
        $this->assertSame($actor->id, User::withTrashed()->find($target->id)?->deleted_by);
    }

    public function test_a_user_cannot_delete_themselves(): void
    {
        $actor = $this->userWithPermissions(UserPermission::Delete);

        $this->actingAs($actor)->delete(route('users.destroy', $actor))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Anda tidak dapat menghapus akun sendiri.']);

        $this->assertNotSoftDeleted($actor);
    }

    public function test_the_last_super_admin_cannot_be_deleted_or_demoted(): void
    {
        $only = $this->superAdmin();
        $other = $this->superAdmin();
        $other->delete();

        $this->actingAs($only)->put(route('users.update', $only), $this->payload(['email' => $only->email, 'roles' => []]));
        $this->assertTrue($only->fresh()?->isSuperAdmin());

        $second = $this->superAdmin();
        $this->actingAs($second)->delete(route('users.destroy', $only))->assertRedirect();
        $this->assertSoftDeleted($only);

        $this->actingAs($second)->delete(route('users.destroy', $second));
        $this->assertNotSoftDeleted($second);
    }
}
