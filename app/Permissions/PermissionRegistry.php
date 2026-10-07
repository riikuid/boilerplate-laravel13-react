<?php

namespace App\Permissions;

use Illuminate\Support\Str;

/** Single list of every permission the application defines. */
final class PermissionRegistry
{
    /** @var list<class-string<PermissionGroup>> */
    public const GROUPS = [
        UserPermission::class,
        RolePermission::class,
        AuditLogPermission::class,
    ];

    /** @return list<string> */
    public static function all(): array
    {
        $names = [];

        foreach (self::GROUPS as $group) {
            foreach ($group::cases() as $case) {
                $names[] = $case->value;
            }
        }

        return $names;
    }

    /**
     * Permissions grouped per module, for the role editor.
     *
     * @return list<array{key: string, label: string, permissions: list<array{name: string, action: string}>}>
     */
    public static function grouped(): array
    {
        return array_map(fn (string $group) => [
            'key' => Str::beforeLast($group::cases()[0]->value, '.'),
            'label' => $group::label(),
            'permissions' => array_map(fn (PermissionGroup $case) => [
                'name' => (string) $case->value,
                'action' => Str::afterLast((string) $case->value, '.'),
            ], $group::cases()),
        ], self::GROUPS);
    }
}
