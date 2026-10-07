import { Form, Link } from '@inertiajs/react';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/users';
import type { RouteFormDefinition } from '@/wayfinder';
import type { UserRow } from '@/types';

type Props = {
    /** Wayfinder form definition: store.form() or update.form(id). */
    formProps: RouteFormDefinition<'post'>;
    user?: UserRow;
    roles: string[];
};

export function UserForm({ formProps, user, roles }: Props) {
    const editing = user !== undefined;

    return (
        <Form {...formProps} className="max-w-xl space-y-6">
            {({ errors, processing }) => (
                <>
                    <FormField id="name" label="Nama" error={errors.name}>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={user?.name}
                            required
                            autoComplete="off"
                        />
                    </FormField>

                    <FormField id="email" label="Email" error={errors.email}>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            defaultValue={user?.email}
                            required
                            autoComplete="off"
                        />
                    </FormField>

                    <FormField
                        id="password"
                        label="Kata sandi"
                        error={errors.password}
                        hint={
                            editing
                                ? 'Kosongkan jika tidak ingin mengubah.'
                                : undefined
                        }
                    >
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            required={!editing}
                            autoComplete="new-password"
                        />
                    </FormField>

                    <fieldset className="grid gap-3">
                        <legend className="mb-1 text-sm font-medium">
                            Peran
                        </legend>
                        {roles.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Belum ada peran.
                            </p>
                        )}
                        {roles.map((role) => (
                            <div key={role} className="flex items-center gap-2">
                                <Checkbox
                                    id={`role-${role}`}
                                    name="roles[]"
                                    value={role}
                                    defaultChecked={user?.roles.includes(role)}
                                />
                                <Label htmlFor={`role-${role}`}>{role}</Label>
                            </div>
                        ))}
                        {errors.roles && (
                            <p className="text-sm text-red-600">
                                {errors.roles}
                            </p>
                        )}
                    </fieldset>

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
