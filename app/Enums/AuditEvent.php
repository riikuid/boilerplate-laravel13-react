<?php

namespace App\Enums;

/**
 * Registry of audit events that are NOT plain model created/updated/deleted
 * (those are named automatically by HasAuditLog). Check here before adding a
 * new name so events stay unique.
 */
enum AuditEvent: string
{
    case AuthLogin = 'auth.login';
    case AuthLogout = 'auth.logout';
    case AuthLoginFailed = 'auth.login_failed';
    case UserRolesChanged = 'user.roles_changed';
    case RolePermissionsChanged = 'role.permissions_changed';
}
