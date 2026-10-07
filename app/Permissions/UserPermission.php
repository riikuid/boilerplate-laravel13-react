<?php

namespace App\Permissions;

enum UserPermission: string implements PermissionGroup
{
    case Read = 'pages.users.read';
    case Create = 'pages.users.create';
    case Update = 'pages.users.update';
    case Delete = 'pages.users.delete';

    public static function label(): string
    {
        return 'Pengguna';
    }
}
