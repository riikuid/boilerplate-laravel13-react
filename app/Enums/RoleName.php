<?php

namespace App\Enums;

/** Roles the application itself relies on. Other roles are plain data managed in the UI. */
enum RoleName: string
{
    /** Bypasses every permission check (Gate::before). Cannot be edited or deleted. */
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
}
