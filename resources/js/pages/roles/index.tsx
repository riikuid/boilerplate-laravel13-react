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
import { Permission } from '@/lib/permissions';
import { create, destroy, edit, index } from '@/routes/roles';
import type { Paginated, RoleRow } from '@/types';

export default function RolesIndex({ roles }: { roles: Paginated<RoleRow> }) {
    const { can } = useCan();
    const [deleting, setDeleting] = useState<RoleRow | null>(null);
    const [processing, setProcessing] = useState(false);

    const columns: Column<RoleRow>[] = [
        {
            key: 'name',
            header: 'Nama',
            sortable: true,
            cell: (role) => (
                <span className="flex items-center gap-2 font-medium">
                    {role.name}
                    {role.is_system && <Badge variant="outline">sistem</Badge>}
                </span>
            ),
        },
        {
            key: 'permissions_count',
            header: 'Hak akses',
            cell: (role) =>
                role.is_super_admin ? 'Semua' : (role.permissions_count ?? 0),
        },
        {
            key: 'users_count',
            header: 'Pengguna',
            cell: (role) => role.users_count ?? 0,
        },
        {
            key: 'actions',
            header: '',
            className: 'w-24 text-right',
            cell: (role) => (
                <div className="flex justify-end gap-1">
                    {can(Permission.Role.Update) && !role.is_super_admin && (
                        <Button
                            variant="ghost"
                            size="icon"
                            asChild
                            aria-label={`Ubah ${role.name}`}
                        >
                            <Link href={edit(role.id)}>
                                <Pencil />
                            </Link>
                        </Button>
                    )}
                    {can(Permission.Role.Delete) && !role.is_system && (
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={`Hapus ${role.name}`}
                            onClick={() => setDeleting(role)}
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
            <Head title="Peran & Hak Akses" />
            <div className="space-y-6 p-4">
                <PageHeader
                    title="Peran & Hak Akses"
                    description="Atur apa yang boleh dilakukan setiap peran."
                    actions={
                        <Can permission={Permission.Role.Create}>
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> Tambah peran
                                </Link>
                            </Button>
                        </Can>
                    }
                />

                <DataTable
                    table={roles}
                    columns={columns}
                    rowKey={(role) => role.id}
                    url={index().url}
                    only="roles"
                    searchPlaceholder="Cari peran..."
                />
            </div>

            <ConfirmDialog
                open={deleting !== null}
                title="Hapus peran?"
                description={`Peran ${deleting?.name ?? ''} akan dihapus. Peran yang masih dipakai pengguna tidak bisa dihapus.`}
                processing={processing}
                onConfirm={confirmDelete}
                onCancel={() => setDeleting(null)}
            />
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: 'Peran & Hak Akses', href: index() }],
};
