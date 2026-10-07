<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Permissions\UserPermission;
use App\Services\UserService;
use App\Support\Flash;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(private readonly UserService $users) {}

    /** @return list<Middleware> */
    public static function middleware(): array
    {
        return [
            new Middleware('can:'.UserPermission::Read->value, only: ['index']),
            new Middleware('can:'.UserPermission::Create->value, only: ['create', 'store']),
            new Middleware('can:'.UserPermission::Update->value, only: ['edit', 'update']),
            new Middleware('can:'.UserPermission::Delete->value, only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $table = TableQuery::for(User::query()->with('roles:id,name'), $request)
            ->searchable('name', 'email')
            ->sortable('name', 'email', 'created_at')
            ->paginate(fn (User $user) => UserResource::make($user)->resolve());

        return Inertia::render('users/index', ['users' => $table]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('users/create', ['roles' => $this->assignableRoles($request)]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->createUser($request->validated());

        Flash::success('Pengguna berhasil ditambahkan.');

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        $history = AuditLog::query()->forModel($user)->with('actor:id,name')->latest('id')->limit(20)->get();

        return Inertia::render('users/edit', [
            'user' => UserResource::make($user)->resolve(),
            'roles' => $this->assignableRoles($request),
            'history' => AuditLogResource::collection($history)->resolve(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->updateUser($user->id, $request->validated());

        Flash::success('Pengguna berhasil diperbarui.');

        return to_route('users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->users->deleteUser($user->id);

        Flash::success('Pengguna berhasil dihapus.');

        return to_route('users.index');
    }

    /**
     * Role names the current user may hand out: super-admin is reserved to super admins.
     *
     * @return list<string>
     */
    private function assignableRoles(Request $request): array
    {
        $actor = $request->user();

        $names = Role::query()
            ->when(! ($actor instanceof User && $actor->isSuperAdmin()), fn ($query) => $query->where('name', '!=', RoleName::SuperAdmin->value))
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (mixed $name): string => (string) $name)
            ->all();

        return array_values($names);
    }
}
