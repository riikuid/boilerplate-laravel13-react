import type { ReactNode } from 'react';
import { useCan } from '@/hooks/use-can';
import type { Permission } from '@/lib/permissions';

export function Can({
    permission,
    children,
}: {
    permission: Permission;
    children: ReactNode;
}) {
    const { can } = useCan();

    return can(permission) ? <>{children}</> : null;
}
