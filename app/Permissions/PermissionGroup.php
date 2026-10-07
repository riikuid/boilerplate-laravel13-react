<?php

namespace App\Permissions;

use BackedEnum;

/**
 * A module's permissions, declared as a string-backed enum whose values follow
 * `{scope}.{module}.{action}` (pages.users.read). Register new enums in
 * PermissionRegistry::GROUPS.
 */
interface PermissionGroup extends BackedEnum
{
    /** Human label of the module, shown in the role editor. */
    public static function label(): string;
}
