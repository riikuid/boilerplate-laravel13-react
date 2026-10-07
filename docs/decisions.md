# Keputusan Arsitektur

Dokumen ini mencatat keputusan dasar boilerplate dan alasannya. Ubah dokumen ini
bersamaan dengan perubahan keputusannya.

## Stack

| Area                | Pilihan                                                 | Alasan                                                                     |
| ------------------- | ------------------------------------------------------- | -------------------------------------------------------------------------- |
| Backend             | Laravel 13, PHP 8.3+ (dev: 8.5)                         | Target utama.                                                              |
| Frontend            | Inertia 3 + React 19 + TypeScript                       | Tanpa API terpisah; routing dan auth tetap di Laravel.                     |
| UI                  | shadcn/ui (Radix) + Tailwind v4                         | Bawaan starter kit resmi; komponen dimiliki project, bukan dependensi.     |
| Route di frontend   | Laravel Wayfinder                                       | Route dan action bertipe, tanpa string URL di React.                       |
| Auth                | Laravel Fortify (starter kit)                           | Login, reset password, verifikasi email, 2FA, passkey.                     |
| Database            | PostgreSQL 17 (test: SQLite in-memory)                  | `lockForUpdate()` bekerja nyata di Postgres. Tes cepat tanpa service.      |
| Cache/queue/session | Redis                                                   | Standar untuk project yang akan tumbuh.                                    |
| RBAC                | spatie/laravel-permission                               | Sama dengan boilerplate lama.                                              |
| Lint & tipe         | Pint, Larastan level 7, `vp check` (lint+format), `tsc` | Bawaan starter kit, dijalankan di CI.                                      |
| Test                | PHPUnit 12                                              | Bawaan starter kit.                                                        |
| Dev environment     | Docker (`./dev`)                                        | Tidak ada PHP/Composer di host; Wayfinder butuh `php` saat build frontend. |

## Pendekatan dari boilerplate lama yang dipertahankan

Aturan lengkap ada di [CONTRIBUTING.md](../CONTRIBUTING.md). Ringkasnya:

- Controller = adapter HTTP. FormRequest = gatekeeper. Service = tempat business logic.
- Proses kritikal: `DB::transaction` + `lockForUpdate()`.
- Pelanggaran business rule memakai `BusinessException`, dirender oleh handler global.
- Audit kolom otomatis lewat trait `UserStamp`.
- Audit trail lewat Observer + trait `HasAuditLog`.
- RBAC satu arah: definisi permission di kode, seeder menyinkronkan ke DB, guard di
  controller dan di UI.

## Yang berubah dan alasannya

| Boilerplate lama                                              | Sekarang                                                                              | Alasan                                                                                                                                     |
| ------------------------------------------------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Blade + Alpine + yajra datatables                             | Inertia + React; tabel server-side lewat `TableQuery` + komponen `DataTable`          | UI jadi komponen bertipe; data tabel lewat props Inertia, tanpa endpoint AJAX khusus.                                                      |
| Konstanta permission + `PermissionDictionary` (beberapa file) | Satu backed enum per modul di `app/Permissions/`, didaftarkan di `PermissionRegistry` | Satu sumber kebenaran. Daftar TypeScript dibuat otomatis dari enum (`permissions:export`), jadi frontend tidak mengetik string permission. |
| Format `pages>master>tariff>read`                             | `pages.master.tariff.read` (titik)                                                    | `>` tidak aman dipakai di middleware (`can:`), URL, dan identifier TS. Pemisah ada di satu tempat (`Permissions\Permission`).              |
| `USER_CHECK` = `hasDirectPermission`                          | `$user->can()` lewat role                                                             | Pengelolaan lewat role lebih mudah dirawat. Permission langsung ke user tetap bisa.                                                        |
| Guard di view `@if (USER_CHECK(...))`                         | `auth.permissions` dibagikan lewat Inertia; hook `useCan()` dan `<Can>`               | Padanan langsung di React. Guard UI hanya kosmetik, guard sebenarnya di server.                                                            |
| Helper `SUCCESS_RESPONSE()`                                   | Flash message Inertia (`->with('success', ...)`) yang ditampilkan sebagai toast       | Redirect-after-POST ala Inertia, bukan respons JSON.                                                                                       |
| `abort(403)` manual di controller                             | Middleware `can:` di route + `authorize()` di FormRequest                             | Guard terlihat di satu tempat (file route).                                                                                                |
| `Handler.php` global                                          | `->withExceptions()` di `bootstrap/app.php`                                           | Cara Laravel 11+.                                                                                                                          |
| Event audit trail didokumentasikan manual                     | Enum `AuditEvent`                                                                     | Tidak ada nama event ganda, dan IDE bisa auto-complete.                                                                                    |

## Fitur yang dibawa

- Manajemen **User**, **Role** (beserta permission-nya), dan **Audit Log** sebagai modul
  referensi lengkap: route → controller → request → service → model → page React → test.
  Modul baru meniru ketiganya.
- Registrasi mandiri **dimatikan**. User dibuat oleh admin. Pilihan ini cocok untuk
  aplikasi internal dan instansi; aktifkan lagi lewat `config/fortify.php` bila perlu.
- Passkey **dimatikan** (dependensi tambahan, belum dibutuhkan). 2FA, verifikasi email,
  dan konfirmasi password tetap aktif.
- Locale `id`. Database dan `config('app.timezone')` tetap UTC; frontend menampilkan
  Asia/Jakarta (`lib/format.ts`). Seluruh teks UI (modul bawaan, halaman auth, dan settings
  bawaan starter kit) berbahasa Indonesia, ditulis langsung tanpa library i18n. Pesan dari server memakai
  `lang/id*` (nama field di `lang/id/validation.php` → `attributes`).

## Sengaja tidak dibawa

- Multi-tenancy, API token/Sanctum, Livewire, library i18n, state manager global,
  CMS/admin panel generik. Tambahkan per project saat benar-benar dibutuhkan.
