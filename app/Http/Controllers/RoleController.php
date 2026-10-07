<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\RoleResource;
use App\Models\AuditLog;
use App\Models\Role;
use App\Permissions\PermissionRegistry;
use App\Permissions\RolePermission;
use App\Services\RoleService;
use App\Support\Flash;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller implements HasMiddleware
{
    public function __construct(private readonly RoleService $roles) {}

    /** @return list<Middleware> */
    public static function middleware(): array
    {
        return [
            new Middleware('can:'.RolePermission::Read->value, only: ['index']),
            new Middleware('can:'.RolePermission::Create->value, only: ['create', 'store']),
            new Middleware('can:'.RolePermission::Update->value, only: ['edit', 'update']),
            new Middleware('can:'.RolePermission::Delete->value, only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $table = TableQuery::for(Role::query()->withCount(['permissions', 'users']), $request)
            ->searchable('name')
            ->sortable('name', 'created_at')
            ->defaultSort('name', 'asc')
            ->paginate(fn (Role $role) => RoleResource::make($role)->resolve());

        return Inertia::render('roles/index', ['roles' => $table]);
    }

    public function create(): Response
    {
        return Inertia::render('roles/create', ['permissionGroups' => PermissionRegistry::grouped()]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roles->createRole($request->validated());

        Flash::success('Peran berhasil ditambahkan.');

        return to_route('roles.index');
    }

    public function edit(Role $role): Response
    {
        $history = AuditLog::query()->forModel($role)->with('actor:id,name')->latest('id')->limit(20)->get();

        return Inertia::render('roles/edit', [
            'role' => [
                ...RoleResource::make($role)->resolve(),
                'permissions' => $role->permissions()->pluck('name')->all(),
            ],
            'permissionGroups' => PermissionRegistry::grouped(),
            'history' => AuditLogResource::collection($history)->resolve(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roles->updateRole((int) $role->id, $request->validated());

        Flash::success('Peran berhasil diperbarui.');

        return to_route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roles->deleteRole((int) $role->id);

        Flash::success('Peran berhasil dihapus.');

        return to_route('roles.index');
    }
}
