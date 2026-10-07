<?php

namespace App\Permissions;

enum RolePermission: string implements PermissionGroup
{
    case Read = 'pages.roles.read';
    case Create = 'pages.roles.create';
    case Update = 'pages.roles.update';
    case Delete = 'pages.roles.delete';

    public static function label(): string
    {
        return 'Peran & Hak Akses';
    }
}
