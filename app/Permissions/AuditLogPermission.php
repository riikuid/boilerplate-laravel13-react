<?php

namespace App\Permissions;

enum AuditLogPermission: string implements PermissionGroup
{
    case Read = 'pages.audit-logs.read';

    public static function label(): string
    {
        return 'Audit Log';
    }
}
