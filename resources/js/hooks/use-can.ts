import { usePage } from '@inertiajs/react';
import type { Permission } from '@/lib/permissions';

/**
 * Permission checks for the UI. This only decides what to *show*;
 * the server guards every action regardless.
 */
export function useCan() {
    const { auth } = usePage().props;
    const granted = new Set(auth.permissions);

    return {
        can: (permission: Permission) => granted.has(permission),
        canAny: (...permissions: Permission[]) =>
            permissions.some((permission) => granted.has(permission)),
    };
}
