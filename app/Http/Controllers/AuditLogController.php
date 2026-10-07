<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Permissions\AuditLogPermission;
use App\Support\TableQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller implements HasMiddleware
{
    /** @return list<Middleware> */
    public static function middleware(): array
    {
        return [new Middleware('can:'.AuditLogPermission::Read->value)];
    }

    public function index(Request $request): Response
    {
        $table = TableQuery::for(AuditLog::query()->with('actor:id,name'), $request)
            ->searchable('event')
            ->sortable('event', 'created_at')
            ->paginate(fn (AuditLog $log) => AuditLogResource::make($log)->resolve());

        return Inertia::render('audit-logs/index', ['logs' => $table]);
    }
}
