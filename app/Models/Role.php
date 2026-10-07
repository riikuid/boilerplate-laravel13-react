<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Traits\HasAuditLog;
use App\Traits\UserStamp;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasAuditLog, UserStamp;

    /** Roles the application itself depends on: they cannot be renamed or deleted. */
    public function isSystemRole(): bool
    {
        return RoleName::tryFrom($this->name) !== null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === RoleName::SuperAdmin->value;
    }
}
