<?php

namespace Tests\Feature;

use App\Permissions\PermissionRegistry;
use App\Permissions\PermissionTypeScriptExporter;
use App\Permissions\UserPermission;
use App\Services\PermissionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_names_follow_the_convention_and_are_unique(): void
    {
        $all = PermissionRegistry::all();

        $this->assertSame($all, array_values(array_unique($all)));

        foreach ($all as $name) {
            $this->assertMatchesRegularExpression('/^[a-z-]+(\.[a-z-]+){2,3}$/', $name);
        }
    }

    public function test_sync_is_idempotent_and_prunes_only_on_request(): void
    {
        $service = app(PermissionSyncService::class);

        $first = $service->sync();
        $second = $service->sync();

        $this->assertSame(count(PermissionRegistry::all()), $first['created']);
        $this->assertSame(0, $second['created']);

        Permission::create(['name' => 'pages.legacy.read', 'guard_name' => 'web']);

        $this->assertSame(0, $service->sync()['pruned']);
        $this->assertDatabaseHas('permissions', ['name' => 'pages.legacy.read']);

        $this->assertSame(1, $service->sync(prune: true)['pruned']);
        $this->assertDatabaseMissing('permissions', ['name' => 'pages.legacy.read']);
    }

    public function test_the_generated_typescript_file_is_up_to_date(): void
    {
        $this->assertSame(
            PermissionTypeScriptExporter::render(),
            file_get_contents(base_path(PermissionTypeScriptExporter::PATH)),
            'Run `php artisan permissions:export` and commit the result.',
        );
    }

    public function test_shared_props_expose_the_users_permissions_and_roles(): void
    {
        $user = $this->userWithPermissions(UserPermission::Read);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', [UserPermission::Read->value])
            ->where('auth.roles', []));
    }

    public function test_a_super_admin_is_shared_every_permission_and_passes_every_gate(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', PermissionRegistry::all()));

        $this->assertTrue($admin->can(UserPermission::Delete->value));
    }

    public function test_grouped_permissions_cover_every_permission(): void
    {
        $names = collect(PermissionRegistry::grouped())->flatMap(fn ($group) => array_column($group['permissions'], 'name'))->all();

        $this->assertEqualsCanonicalizing(PermissionRegistry::all(), $names);
    }
}
