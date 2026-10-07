# Membuat modul baru

Contoh acuan lengkap: **Pengguna** (`UserController`, `UserService`, `users/*.tsx`). Untuk
modul yang lebih sederhana, lihat **Peran**. Berikut urutan kerjanya, dengan contoh modul "Produk".

## 1. Database

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
    $table->softDeletes();
    $table->userStamps();
});
```

## 2. Model

```php
class Product extends Model
{
    use HasAuditLog, HasFactory, SoftDeletes, UserStamp;

    #[Fillable(['name'])]
    ...
}
```

Daftarkan `'product' => Product::class` di `AppServiceProvider::configureMorphMap()`.

## 3. Permission

`app/Permissions/ProductPermission.php`: enum `implements PermissionGroup` (`pages.products.*`),
tambahkan ke `PermissionRegistry::GROUPS`, lalu:

```bash
./dev artisan permissions:export
./dev artisan permissions:sync
```

## 4. Request, Service, Controller, Route

- `StoreProductRequest` / `UpdateProductRequest`: `authorize()` + `rules()`.
- `ProductService`: `createProduct`, `updateProduct` (`lockForUpdate`), `deleteProduct`. Pelanggaran rule → `BusinessException`.
- `ProductController implements HasMiddleware`: guard per action, panggil service, `Flash::success`, `Inertia::render`.
- `ProductResource`: bentuk data untuk React.
- Route: `Route::resource('products', ProductController::class)->except('show');` di grup `auth, verified`.

## 5. Frontend

```bash
./dev artisan wayfinder:generate --with-form   # atau biarkan dev server yang membuatnya
```

- `resources/js/pages/products/{index,create,edit}.tsx`: salin dari `users/`.
- `components/products/product-form.tsx`: salin `user-form.tsx`.
- Sidebar: tambahkan item ke `adminNavItems` di `components/app-sidebar.tsx` dengan `permission`.
- Label audit: `resources/js/lib/audit.ts`.
- Nama field baru: tambahkan ke `attributes` di `lang/id/validation.php` agar pesan validasi berbahasa Indonesia.

## 6. Test

Salin pola `tests/Feature/Users/UserManagementTest.php`: 403 tanpa permission, daftar + pencarian,
store/update/destroy, validasi, setiap aturan bisnis dan pesan error-nya.

## 7. Dokumentasi

Tambahkan baris modul di `docs/flow-system/rbac.md`.

## Checklist

- [ ] Migration memakai `userStamps()` (+ `softDeletes()` bila perlu)
- [ ] Model: `UserStamp`, `HasAuditLog`, terdaftar di morph map
- [ ] Permission: enum, registry, `permissions:export`, `permissions:sync`
- [ ] Service: transaction + lock, `BusinessException`
- [ ] Controller: guard `HasMiddleware`; tanpa query bisnis
- [ ] FormRequest: `authorize()`
- [ ] Halaman React + item sidebar dengan `permission`
- [ ] Test (termasuk 403) dan `composer ci:check` hijau
- [ ] `rbac.md` diperbarui
