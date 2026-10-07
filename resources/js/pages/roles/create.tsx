import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { RoleForm } from '@/components/roles/role-form';
import { create, index, store } from '@/routes/roles';
import type { PermissionGroup } from '@/types';

export default function RolesCreate({
    permissionGroups,
}: {
    permissionGroups: PermissionGroup[];
}) {
    return (
        <>
            <Head title="Tambah peran" />
            <div className="space-y-6 p-4">
                <PageHeader title="Tambah peran" />
                <RoleForm
                    formProps={store.form()}
                    permissionGroups={permissionGroups}
                />
            </div>
        </>
    );
}

RolesCreate.layout = {
    breadcrumbs: [
        { title: 'Peran & Hak Akses', href: index() },
        { title: 'Tambah', href: create() },
    ],
};
