import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { UserForm } from '@/components/users/user-form';
import { create, index, store } from '@/routes/users';

export default function UsersCreate({ roles }: { roles: string[] }) {
    return (
        <>
            <Head title="Tambah pengguna" />
            <div className="space-y-6 p-4">
                <PageHeader title="Tambah pengguna" />
                <UserForm formProps={store.form()} roles={roles} />
            </div>
        </>
    );
}

UsersCreate.layout = {
    breadcrumbs: [
        { title: 'Pengguna', href: index() },
        { title: 'Tambah', href: create() },
    ],
};
