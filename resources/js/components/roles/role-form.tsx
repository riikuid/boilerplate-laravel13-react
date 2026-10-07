import { Form, Link } from '@inertiajs/react';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index } from '@/routes/roles';
import type { RouteFormDefinition } from '@/wayfinder';
import type { PermissionGroup } from '@/types';

const ACTIONS: { action: string; label: string }[] = [
    { action: 'read', label: 'Lihat' },
    { action: 'create', label: 'Tambah' },
    { action: 'update', label: 'Ubah' },
    { action: 'delete', label: 'Hapus' },
];

type Props = {
    /** Wayfinder form definition: store.form() or update.form(id). */
    formProps: RouteFormDefinition<'post'>;
    permissionGroups: PermissionGroup[];
    role?: { name: string; permissions: string[]; is_system: boolean };
};

export function RoleForm({ formProps, permissionGroups, role }: Props) {
    return (
        <Form {...formProps} className="max-w-3xl space-y-6">
            {({ errors, processing }) => (
                <>
                    <FormField
                        id="name"
                        label="Nama peran"
                        error={errors.name}
                        hint={
                            role?.is_system
                                ? 'Peran bawaan sistem, namanya tidak dapat diubah.'
                                : 'Huruf kecil, angka, dan tanda hubung. Contoh: verifikator.'
                        }
                    >
                        <Input
                            id="name"
                            name="name"
                            defaultValue={role?.name}
                            readOnly={role?.is_system}
                            required
                            autoComplete="off"
                        />
                    </FormField>

                    <div className="space-y-2">
                        <p className="text-sm font-medium">Hak akses</p>
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead>Modul</TableHead>
                                        {ACTIONS.map(({ action, label }) => (
                                            <TableHead
                                                key={action}
                                                className="text-center"
                                            >
                                                {label}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {permissionGroups.map((group) => (
                                        <TableRow key={group.key}>
                                            <TableCell className="font-medium">
                                                {group.label}
                                            </TableCell>
                                            {ACTIONS.map(
                                                ({ action, label }) => {
                                                    const permission =
                                                        group.permissions.find(
                                                            (item) =>
                                                                item.action ===
                                                                action,
                                                        );

                                                    return (
                                                        <TableCell
                                                            key={action}
                                                            className="text-center"
                                                        >
                                                            {permission && (
                                                                <Checkbox
                                                                    name="permissions[]"
                                                                    value={
                                                                        permission.name
                                                                    }
                                                                    defaultChecked={role?.permissions.includes(
                                                                        permission.name,
                                                                    )}
                                                                    aria-label={`${group.label}: ${label}`}
                                                                />
                                                            )}
                                                        </TableCell>
                                                    );
                                                },
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        {errors.permissions && (
                            <p className="text-sm text-red-600">
                                {errors.permissions}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            Simpan
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={index()}>Batal</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
