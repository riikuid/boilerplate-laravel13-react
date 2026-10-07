import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Can } from '@/components/can';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { formatDateTime } from '@/lib/format';
import { Permission } from '@/lib/permissions';
import { create, destroy, edit, index } from '@/routes/users';
import type { Paginated, UserRow } from '@/types';

export default function UsersIndex({ users }: { users: Paginated<UserRow> }) {
    const { can } = useCan();
    const [deleting, setDeleting] = useState<UserRow | null>(null);
    const [processing, setProcessing] = useState(false);

    const columns: Column<UserRow>[] = [
        {
            key: 'name',
            header: 'Nama',
            sortable: true,
            cell: (user) => <span className="font-medium">{user.name}</span>,
        },
        {
            key: 'email',
            header: 'Email',
            sortable: true,
            cell: (user) => user.email,
        },
        {
            key: 'roles',
            header: 'Peran',
            cell: (user) => (
                <div className="flex flex-wrap gap-1">
                    {user.roles.map((role) => (
                        <Badge key={role} variant="secondary">
                            {role}
                        </Badge>
                    ))}
                </div>
            ),
        },
        {
            key: 'created_at',
            header: 'Dibuat',
            sortable: true,
            cell: (user) => formatDateTime(user.created_at),
        },
        {
            key: 'actions',
            header: '',
            className: 'w-24 text-right',
            cell: (user) => (
                <div className="flex justify-end gap-1">
                    {can(Permission.User.Update) && (
                        <Button
                            variant="ghost"
                            size="icon"
                            asChild
                            aria-label={`Ubah ${user.name}`}
                        >
                            <Link href={edit(user.id)}>
                                <Pencil />
                            </Link>
                        </Button>
                    )}
                    {can(Permission.User.Delete) && (
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={`Hapus ${user.name}`}
                            onClick={() => setDeleting(user)}
                        >
                            <Trash2 />
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(destroy(deleting.id).url, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <>
            <Head title="Pengguna" />
            <div className="space-y-6 p-4">
                <PageHeader
                    title="Pengguna"
                    description="Kelola akun yang dapat masuk ke aplikasi."
                    actions={
                        <Can permission={Permission.User.Create}>
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> Tambah pengguna
                                </Link>
                            </Button>
                        </Can>
                    }
                />

                <DataTable
                    table={users}
                    columns={columns}
                    rowKey={(user) => user.id}
                    url={index().url}
                    only="users"
                    searchPlaceholder="Cari nama atau email..."
                />
            </div>

            <ConfirmDialog
                open={deleting !== null}
                title="Hapus pengguna?"
                description={`Akun ${deleting?.name ?? ''} tidak akan bisa masuk lagi. Riwayatnya tetap tersimpan.`}
                processing={processing}
                onConfirm={confirmDelete}
                onCancel={() => setDeleting(null)}
            />
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Pengguna', href: index() }],
};
