<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $meta
     */
    public function record(
        AuditEvent|string $event,
        ?Model $auditable = null,
        array $old = [],
        array $new = [],
        array $meta = [],
    ): AuditLog {
        $request = app()->runningInConsole() ? null : request();

        return AuditLog::create([
            'event' => $event instanceof AuditEvent ? $event->value : $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'actor_id' => Auth::id(),
            'old_values' => $old === [] ? null : $old,
            'new_values' => $new === [] ? null : $new,
            'meta' => [
                ...$meta,
                ...($request ? ['ip' => $request->ip(), 'user_agent' => $request->userAgent()] : []),
            ] ?: null,
        ]);
    }
}
