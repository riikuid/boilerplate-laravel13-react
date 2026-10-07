<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Permissions\PermissionRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'roles' => $this->rolesFor($request->user()),
                'permissions' => $this->permissionsFor($request->user()),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Mirrors the server-side checks so the UI can hide what the user cannot do.
     * Cosmetic only: every action is still guarded on the server.
     *
     * @return list<string>
     */
    private function permissionsFor(?object $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        if ($user->isSuperAdmin()) {
            return PermissionRegistry::all();
        }

        return array_values($user->getAllPermissions()->map(fn ($permission): string => (string) $permission->name)->all());
    }

    /** @return list<string> */
    private function rolesFor(?object $user): array
    {
        return $user instanceof User
            ? array_values($user->getRoleNames()->map(fn ($name): string => (string) $name)->all())
            : [];
    }
}
