import { Head } from '@inertiajs/react';
import { AuditTimeline } from '@/components/audit-timeline';
import { PageHeader } from '@/components/page-header';
import { RoleForm } from '@/components/roles/role-form';
import { index, update } from '@/routes/roles';
import type { AuditLogEntry, PermissionGroup, RoleRow } from '@/types';

export default function RolesEdit({
    role,
    permissionGroups,
    history,
}: {
    role: RoleRow & { permissions: string[] };
    permissionGroups: PermissionGroup[];
    history: AuditLogEntry[];
}) {
    return (
        <>
            <Head title={`Ubah ${role.name}`} />
            <div className="space-y-6 p-4">
                <PageHeader title={`Ubah ${role.name}`} />
                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <RoleForm
                        formProps={update.form(role.id)}
                        permissionGroups={permissionGroups}
                        role={role}
                    />
                    <AuditTimeline entries={history} />
                </div>
            </div>
        </>
    );
}

RolesEdit.layout = {
    breadcrumbs: [
        { title: 'Peran & Hak Akses', href: index() },
        { title: 'Ubah', href: index() },
    ],
};
