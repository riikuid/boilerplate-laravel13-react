# Contributing Guide

Standar pengembangan untuk project berbasis boilerplate ini (Laravel 13 + Inertia + React).
Tujuannya menjaga konsistensi, maintainability, dan kualitas kode.

## Dokumentasi teknis

Baca dokumen yang relevan sebelum mengembangkan fitur baru. Semua ada di `docs/`.

| Dokumen                                                            | Isi                                                                                                  | Baca ketika                              |
| ------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------- | ---------------------------------------- |
| [docs/new-module.md](docs/new-module.md)                           | Langkah demi langkah membuat modul baru (route → controller → request → service → page React → test) | Menambah fitur/modul                     |
| [docs/flow-system/rbac.md](docs/flow-system/rbac.md)               | Sistem permission: enum, registry, seeder, guard di server dan UI                                    | Menambah permission atau membatasi akses |
| [docs/flow-system/audit-trail.md](docs/flow-system/audit-trail.md) | Audit trail: `HasAuditLog`, `AuditLogger`, daftar event, komponen UI                                 | Mencatat perubahan data suatu modul      |
| [docs/frontend.md](docs/frontend.md)                               | Konvensi React/Inertia: DataTable, `useCan`, form, toast, Wayfinder                                  | Mengerjakan halaman                      |
| [docs/development.md](docs/development.md)                         | Docker, perintah `./dev`, port, troubleshooting                                                      | Setup dan masalah environment            |
| [docs/decisions.md](docs/decisions.md)                             | Keputusan arsitektur dan alasannya, termasuk apa yang berubah dari boilerplate lama                  | Mempertanyakan atau mengubah arah        |

---

# 1. Prinsip umum

- Business logic di Service Layer
- Validasi di FormRequest
- Controller hanya menangani HTTP
- Semua proses kritikal memakai Transaction + Lock
- Server adalah satu-satunya sumber kebenaran: guard di UI hanya kosmetik

---

# 2. Arsitektur

```
Browser (React page)
        ↓  Inertia visit / <Form>
Route  → middleware `can:{permission}` (di controller via HasMiddleware)
        ↓
Controller      adapter HTTP
        ↓
FormRequest     authorize + validasi + transformasi input
        ↓
Service         business logic
        ↓
Transaction + lockForUpdate
        ↓
Model           (+ UserStamp, HasAuditLog)
```

## 2.1 Struktur folder

```
app/
 ├── Console/Commands/        permissions:sync, permissions:export, app:create-admin
 ├── Enums/                   RoleName, AuditEvent
 ├── Exceptions/              BusinessException dan turunannya
 ├── Http/
 │    ├── Controllers/
 │    ├── Middleware/         HandleInertiaRequests (shared props: auth.permissions)
 │    ├── Requests/
 │    └── Resources/          bentuk data yang dikirim ke React
 ├── Models/
 ├── Permissions/             enum permission per modul + PermissionRegistry
 ├── Services/                *Service.php, AuditLogger
 ├── Support/                 Flash, TableQuery
 └── Traits/                  UserStamp, HasAuditLog
resources/js/
 ├── components/              komponen bersama (DataTable, Can, ConfirmDialog, ...)
 ├── hooks/                   useCan, ...
 ├── layouts/
 ├── lib/                     format, audit, permissions.ts (generated)
 ├── pages/                   satu folder per modul, nama = komponen Inertia
 └── types/
```

## 2.2 Controller

Controller hanya bertugas:

- Menerima request
- Memanggil service
- Mengembalikan respons (`Inertia::render` atau redirect + `Flash`)

Controller tidak boleh:

- Query database langsung untuk fitur bisnis (query baca untuk daftar master sederhana lewat
  `TableQuery` diperbolehkan)
- Menghitung qty atau menulis business rule
- Mengatur status entity

Guard permission dipasang di controller lewat `HasMiddleware`, bukan `abort()` manual:

```php
public static function middleware(): array
{
    return [
        new Middleware('can:'.UserPermission::Read->value, only: ['index']),
        new Middleware('can:'.UserPermission::Create->value, only: ['create', 'store']),
    ];
}
```

### Contoh benar

```php
public function store(StoreUserRequest $request): RedirectResponse
{
    $this->users->createUser($request->validated());

    Flash::success('Pengguna berhasil ditambahkan.');

    return to_route('users.index');
}
```

## 2.3 Service layer

Semua business logic ada di Service. Service bertanggung jawab atas:

- Validasi business rule
- Update beberapa model
- Transaction dan locking
- Manajemen status
- Mencatat event audit domain (`AuditLogger`)

```php
DB::transaction(function () use ($id) {
    $model = Model::lockForUpdate()->findOrFail($id);

    // business logic
});
```

Service menerima `array` hasil `validated()` atau tipe sederhana, tidak menerima `Request`.

## 2.4 FormRequest

FormRequest bertugas:

- `authorize()`: cek permission (`$this->user()?->can(...)`)
- Validasi input
- Transformasi data (normalisasi email, parsing tanggal, nilai turunan)

Business rule tidak boleh di FormRequest.

Catatan form HTML: checkbox yang semuanya dikosongkan tidak mengirim apa pun. Pada request
update, anggap field array yang tidak ada sebagai `[]` (lihat `UpdateUserRequest`).

## 2.5 Trait UserStamp

Mengisi `created_by`, `updated_by`, `deleted_by` otomatis dari user yang login.

```php
// migration
$table->softDeletes();
$table->userStamps();

// model
use SoftDeletes, UserStamp;
```

Tanpa user yang login (console, queue) kolom dibiarkan kosong.

## 2.6 Trait HasAuditLog

Mencatat created / updated / deleted / restored ke `audit_logs`. Lihat
[docs/flow-system/audit-trail.md](docs/flow-system/audit-trail.md).

---

# 3. Exception handling

Semua pelanggaran business rule memakai `BusinessException` (atau turunan domain-nya).
Handler global di `bootstrap/app.php` merender:

- request Inertia → redirect back + toast error
- request JSON/API → `{"message": "...", "code": "..."}` dengan status dari exception

Jadi controller tidak perlu `try/catch`.

Jangan gunakan:

- `abort()` untuk business rule
- `return false` / return error dari service
- `throw new \Exception()` generik

```php
if ($totalQty !== $do->qty_total) {
    throw new BusinessException('Total SDO tidak sama dengan total DO.', 422, 'split_qty_mismatch');
}
```

Buat subclass per domain bila perlu (contoh: `ProtectedRecordException`, status 409).

---

# 4. Database

## 4.1 Locking

Operasi berikut wajib `lockForUpdate()` di dalam transaction:

- Update qty / saldo / stok
- Generate nomor dokumen
- Ubah status entity
- Operasi split / merge
- Memeriksa lalu mengubah (cek "last super admin", cek "role masih dipakai")

`lockForUpdate()` tidak bisa digabung dengan agregat (`count()`); ambil id-nya lalu hitung di PHP.

## 4.2 Aturan lain

- Gunakan `exists:`/`unique:` validation untuk foreign key
- Model yang di-audit atau diberi role wajib didaftarkan di morph map (`AppServiceProvider`)
- Selalu pakai `App\Models\Role`, bukan `Spatie\Permission\Models\Role`, supaya audit dan stamp aktif

---

# 5. Code style

- Class: PascalCase. Method: camelCase. Variable kode: camelCase. Kolom database: snake_case.
- Nama method service: kata kerja + objek (`createUser`, `splitDo`, `generateSingleDo`).
  Hindari `process()`, `handle()`, `doSomething()`.
- PHP: Pint (`composer lint`). Static analysis: Larastan level 7 (`composer types:check`).
- TypeScript: `npm run check` (lint + format), `npm run types:check`.
- UI berbahasa Indonesia. Nama file React: `kebab-case.tsx`, komponen: PascalCase.

---

# 6. Transaksi dan error

Jangan:

- Nested transaction tanpa alasan jelas
- Menangkap error di dalam transaction lalu `return false`
- Silent error

Wajib:

- `throw` exception
- Biarkan transaction rollback otomatis

---

# 7. Git workflow

Branch:

```
feature/{short-description}
fix/{short-description}
refactor/{short-description}
hotfix/{short-description}
```

Commit:

```
type(scope): short description [ticket]
```

(ticket opsional). Type: `feat`, `fix`, `refactor`, `chore`, `docs`, `perf`, `test`, `style`.

```
feat(do): add split do service
fix(mdo): fix remaining qty calculation
```

---

# 8. Pull request

Setiap PR harus:

- Tidak ada debug code (`dd`, `dump`, `console.log`)
- Tidak ada kode lama yang di-comment
- `composer ci:check` hijau (Pint, Larastan, `vp check`, `tsc`, test)
- Fitur bisnis mengikuti pola Service
- Permission baru: konstanta enum, registry, `permissions:export` dijalankan, dan dites (lihat bagian 12)
- Perilaku baru ada test-nya (tidak hanya "tidak error")

---

# 9. Performa

Hindari: query di dalam loop, filter collection berulang, menghitung ulang data yang sudah ada.

Gunakan: eager loading (`with`), `keyBy()`, `whereIn()`, `select` kolom yang perlu.
Pada React Resource, kirim hanya field yang dipakai halaman.

---

# 10. Keamanan

- Jangan percaya data dari frontend; semua nilai turunan (total, qty, status) dihitung di server
- Semua aksi kritikal dalam transaction
- Guard permission di server selalu ada, walaupun tombol sudah disembunyikan di UI
- Kolom yang boleh di-sort/cari di tabel harus di-whitelist (`TableQuery::sortable/searchable`)
- Jangan kirim model mentah ke Inertia; bentuk lewat `JsonResource`

---

# 11. Filosofi backend

- Controller = Adapter
- Service = Brain
- Model = Data structure
- Request = Gatekeeper
- Exception = Guard
- Transaction = Safety

## Jangan

- Query di controller untuk fitur bisnis
- Menghitung qty di frontend
- Memanipulasi status secara manual di luar service
- Melewatkan `lockForUpdate`
- Memakai exception generik
- `return false` dari service

---

# 12. Permission (RBAC)

Detail dan alasan: [docs/flow-system/rbac.md](docs/flow-system/rbac.md).

```
PermissionGroup enum ──▶ PermissionRegistry ──▶ permissions:sync ──▶ DB
        │                                                            │
        └──▶ permissions:export ──▶ resources/js/lib/permissions.ts  ▼
                                      useCan() / <Can> / nav     Gate::before + can:
```

Format nama: `{scope}.{module}.{action}` (titik), semua lowercase, tiga atau empat segmen:
`pages.users.read`, `pages.audit-logs.read`, `auth.user.delete`.

Menambah permission:

1. Buat/ubah enum di `app/Permissions/` (implementasi `PermissionGroup`).
2. Daftarkan di `PermissionRegistry::GROUPS`.
3. `./dev artisan permissions:export` (perbarui TypeScript; test akan gagal jika lupa).
4. `./dev artisan permissions:sync` di tiap environment (tidak menghapus yang lama; `--prune` untuk membersihkan).
5. Guard di controller (`HasMiddleware` + `can:`) dan `authorize()` di FormRequest.
6. Guard di UI: `<Can permission={Permission.X.Y}>`, `useCan()`, atau `permission` pada `NavItem`.
7. Tambahkan test: 403 tanpa permission, 2xx dengan permission.
8. Perbarui tabel di `docs/flow-system/rbac.md`.

---

# 13. Frontend

Detail: [docs/frontend.md](docs/frontend.md).

- Halaman di `resources/js/pages/{modul}/{aksi}.tsx`; nama = komponen Inertia yang dirender controller
- Route dan action dipanggil lewat Wayfinder (`@/routes/users`), tanpa string URL
- Form: `<Form>` Inertia dengan `store.form()` / `update.form(id)`; error dari `errors.*`
- Tabel server-side: `TableQuery` di controller + `<DataTable>` di React
- Toast sukses/gagal lewat `Flash::success/error` di server
- Jangan simpan data server di state lokal; ambil dari props Inertia

---

# 14. Kualitas dan test

- `./dev test` menjalankan Pint, Larastan, dan PHPUnit. Frontend: `./dev npm run check`, `./dev npm run types:check`, `./dev npm test` (Vitest + jsdom).
- Logika UI yang bisa salah diam-diam (tabel, izin, format) wajib punya test frontend `*.test.ts(x)` di sebelah file-nya.
- Test fitur memakai SQLite in-memory dan `withoutVite()`; tidak butuh build frontend.
- Test mengunci perilaku (403, validasi, rollback, audit), bukan implementasi.
- Fitur yang bergantung pada locking atau fitur khusus PostgreSQL perlu diverifikasi juga
  di Postgres (`./dev up`), karena `lockForUpdate()` tidak berefek di SQLite.
