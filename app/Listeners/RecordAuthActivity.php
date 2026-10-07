<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class RecordAuthActivity
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->record(AuditEvent::AuthLogin, $event->user);
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->record(AuditEvent::AuthLogout, $event->user);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $this->audit->record(
            AuditEvent::AuthLoginFailed,
            $event->user instanceof User ? $event->user : null,
            meta: ['email' => $event->credentials['email'] ?? null],
        );
    }
}
