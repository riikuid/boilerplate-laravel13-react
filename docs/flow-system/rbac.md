# RBAC

Berbasis `spatie/laravel-permission`. Satu sumber kebenaran: enum PHP.

## Alur

```
app/Permissions/*Permission.php   (enum, implements PermissionGroup)
        │
        ├─ PermissionRegistry::GROUPS ──▶ permissions:sync ──▶ tabel permissions
        │                                                         │
        │                                          role_has_permissions / model_has_permissions
        │
        └─ permissions:export ──▶ resources/js/lib/permissions.ts  (generated, di-commit)
```

- `Gate::before` (AppServiceProvider): user ber-role `super-admin` lolos semua pengecekan.
  Role itu tidak menyimpan permission apa pun.
- Role selain itu adalah data. Dikelola di UI **Peran & Hak Akses**.
- Role `admin` di-seed dengan semua permission. Sempitkan per project.
- `HandleInertiaRequests` membagikan `auth.roles` dan `auth.permissions` ke React.

## Penamaan

`{scope}.{module}.{action}`, lowercase, tanda hubung untuk kata majemuk.

| Contoh                       | Arti                         |
| ---------------------------- | ---------------------------- |
| `pages.users.read`           | halaman/daftar pengguna      |
| `pages.audit-logs.read`      | halaman audit log            |
| `pages.master.tariff.update` | empat segmen untuk sub-modul |
| `auth.user.delete`           | operasi di luar halaman      |

Action standar: `read`, `create`, `update`, `delete`. Action lain (mis. `approve`, `export`)
boleh; role editor menampilkan kolom standar dan action khusus perlu ditambahkan ke
`ACTIONS` di `components/roles/role-form.tsx`.

## Status per modul

Perbarui tabel ini setiap menambah modul.

| Modul     | Permission                                | Seeder | Controller | Request | UI  |
| --------- | ----------------------------------------- | ------ | ---------- | ------- | --- |
| Pengguna  | `pages.users.{read,create,update,delete}` | ✅     | ✅         | ✅      | ✅  |
| Peran     | `pages.roles.{read,create,update,delete}` | ✅     | ✅         | ✅      | ✅  |
| Audit log | `pages.audit-logs.read`                   | ✅     | ✅         | n/a     | ✅  |

## Aturan bawaan yang perlu diketahui

- Hanya super admin yang boleh memberi role `super-admin`, serta mengubah/menghapus akun super admin.
- Super admin terakhir tidak bisa dihapus atau dicabut perannya.
- Role `super-admin` dan `admin` adalah role sistem: tidak bisa dihapus; `admin` tidak bisa di-rename.
- Role yang masih dipakai user tidak bisa dihapus.

## Test

Gunakan helper `TestCase::userWithPermissions(UserPermission::Read, ...)` dan `superAdmin()`.
Setiap modul wajib punya test "403 tanpa permission".
