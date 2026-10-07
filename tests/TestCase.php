<?php

namespace Tests;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionSyncService;
use BackedEnum;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests assert on Inertia props; they must not depend on a built frontend.
        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /** A verified user holding exactly the given permissions (enum cases or names). */
    protected function userWithPermissions(BackedEnum|string ...$permissions): User
    {
        app(PermissionSyncService::class)->sync();

        $user = User::factory()->create();
        $user->givePermissionTo(array_map(
            fn (BackedEnum|string $permission) => $permission instanceof BackedEnum ? $permission->value : $permission,
            $permissions,
        ));

        return $user;
    }

    protected function superAdmin(): User
    {
        Role::findOrCreate(RoleName::SuperAdmin->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(RoleName::SuperAdmin->value);

        return $user;
    }
}
