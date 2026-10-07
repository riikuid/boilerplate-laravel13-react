import { Head } from '@inertiajs/react';
import { AuditTimeline } from '@/components/audit-timeline';
import { PageHeader } from '@/components/page-header';
import { UserForm } from '@/components/users/user-form';
import { index, update } from '@/routes/users';
import type { AuditLogEntry, UserRow } from '@/types';

export default function UsersEdit({
    user,
    roles,
    history,
}: {
    user: UserRow;
    roles: string[];
    history: AuditLogEntry[];
}) {
    return (
        <>
            <Head title={`Ubah ${user.name}`} />
            <div className="space-y-6 p-4">
                <PageHeader title={`Ubah ${user.name}`} />
                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <UserForm
                        formProps={update.form(user.id)}
                        user={user}
                        roles={roles}
                    />
                    <AuditTimeline entries={history} />
                </div>
            </div>
        </>
    );
}

UsersEdit.layout = {
    breadcrumbs: [
        { title: 'Pengguna', href: index() },
        { title: 'Ubah', href: index() },
    ],
};
