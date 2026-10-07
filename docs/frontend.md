# Frontend

React 19 + TypeScript + Inertia 3 + Tailwind v4 + shadcn/ui. React Compiler aktif.

## Struktur

| Lokasi                                        | Isi                                                               |
| --------------------------------------------- | ----------------------------------------------------------------- |
| `resources/js/pages/{modul}/{aksi}.tsx`       | halaman; path = nama komponen di `Inertia::render('users/index')` |
| `resources/js/components/`                    | komponen bersama; `ui/` = shadcn (jangan diedit sembarangan)      |
| `resources/js/layouts/`                       | layout; `app.tsx` memilih layout dari prefix nama halaman         |
| `resources/js/routes`, `actions`, `wayfinder` | **generated** Wayfinder (di-gitignore)                            |
| `resources/js/lib/permissions.ts`             | **generated** dari enum PHP (di-commit)                           |
| `resources/js/types/`                         | tipe bersama (`Paginated<T>`, `UserRow`, ...)                     |

## Route bertipe (Wayfinder)

```tsx
import { create, edit, index } from '@/routes/users';

<Link href={create()} />
router.get(index().url, { search: 'abc' });
<Form {...update.form(user.id)}>
```

Wayfinder dibuat ulang otomatis saat dev server berjalan; manual: `./dev artisan wayfinder:generate --with-form`.

## Izin di UI

```tsx
import { Can } from '@/components/can';
import { useCan } from '@/hooks/use-can';
import { Permission } from '@/lib/permissions';

<Can permission={Permission.User.Create}>
    <Button>Tambah</Button>
</Can>;

const { can } = useCan();
can(Permission.User.Update) && <EditButton />;
```

Menu sidebar: beri `permission` pada `NavItem`; item disembunyikan otomatis. Ini hanya kosmetik; server tetap menolak (403).

## Tabel server-side

Server:

```php
$table = TableQuery::for(User::query(), $request)
    ->searchable('name', 'email')
    ->sortable('name', 'email', 'created_at')
    ->paginate(fn (User $u) => UserResource::make($u)->resolve());

return Inertia::render('users/index', ['users' => $table]);
```

Client:

```tsx
<DataTable
    table={users}
    columns={columns}
    rowKey={(u) => u.id}
    url={index().url}
    only="users"
/>
```

`only` adalah nama prop, jadi hanya tabel yang di-reload. State (search/sort/page) ada di URL.

## Form

```tsx
<Form {...store.form()}>
    {({ errors, processing }) => (
        <FormField id="name" label="Nama" error={errors.name}>
            <Input id="name" name="name" />
        </FormField>
    )}
</Form>
```

- Checkbox banyak: `name="roles[]"` dengan `value`. Jika semua dikosongkan, field tidak terkirim;
  request update harus menganggapnya `[]`.
- Validasi Laravel muncul otomatis di `errors`.

## Toast

Server: `Flash::success('...')` / `Flash::error('...')` (`App\Support\Flash`).
`BusinessException` otomatis menjadi toast error. Client sudah memasang `useFlashToast`.

## Tanggal

API mengirim ISO UTC. Tampilkan dengan `formatDateTime()` dari `lib/format.ts` (Asia/Jakarta).

## Test

Vitest lewat `vp test`, lingkungan jsdom, file `*.test.ts(x)` di sebelah kode. Import dari `vite-plus/test`.
`@inertiajs/react` di-mock per file (`vi.mock`); contoh: `components/data-table.test.tsx`,
`components/can.test.tsx`. Pembersihan DOM dipasang di `test-setup.ts`.

## Pengecekan

```bash
./dev npm run check        # lint + format (vp)
./dev npm run check:fix
./dev npm run types:check  # tsc
./dev npm test             # vitest
```

Peringatan lint dianggap error (`denyWarnings`).
