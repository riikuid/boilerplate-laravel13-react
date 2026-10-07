# Development

Semua perintah (PHP, Composer, Node) jalan di container; tidak ada yang perlu dipasang di host selain Docker.

## Mulai dari nol

```bash
./dev setup        # build image, composer install, npm install, key:generate, migrate --seed
./dev up -d        # app (:8300), assets (:5173), queue, postgres, redis, mailpit (:8025)
```

Login lokal: `admin@example.com` / `password` (hanya dibuat di environment `local`).
Production: `php artisan app:create-admin`.

## Perintah

| Perintah                               | Fungsi                                       |
| -------------------------------------- | -------------------------------------------- |
| `./dev up [-d]` / `down`               | jalankan / matikan stack                     |
| `./dev artisan ...`                    | `php artisan ...`                            |
| `./dev composer ...` / `./dev npm ...` | Composer / npm                               |
| `./dev test`                           | Pint + Larastan + PHPUnit (SQLite in-memory) |
| `./dev sh`                             | shell di container                           |

## Port

Diatur di `.env` (`APP_PORT`, `VITE_PORT`, `FORWARD_DB_PORT`, `FORWARD_MAILPIT_PORT`).
Default sengaja tidak memakai 8000/8080/5432 agar tidak bentrok dengan project lain di mesin yang sama.
Bila bentrok, ubah di `.env`; ubah juga `APP_URL`.

## Catatan teknis

- `.env` memakai nama service docker (`DB_HOST=postgres`, `REDIS_HOST=redis`). `artisan serve`
  membuang environment variable yang juga ada di `.env`, sehingga override dari compose tidak
  berlaku; karena itu nilai itu ada di `.env`.
- Dev server aset berjalan di `0.0.0.0`; `server.hmr.host = 'localhost'` di `vite.config.ts`
  membuat browser memakai alamat yang bisa dijangkau.
- Wayfinder (Laravel 13) mengeluarkan method `QUERY` untuk `Route::redirect` yang belum dikenal
  tipe TS-nya. Folder `resources/js/actions/Illuminate` dikecualikan dari `tsc`
  (`tsconfig.json`). Hapus pengecualian itu jika Wayfinder sudah memperbaikinya.
- `lockForUpdate()` tidak berefek di SQLite (test). Verifikasi alur yang bergantung pada lock di Postgres.
- Pada environment tanpa Docker (CI), `.env.example` perlu disesuaikan hostnya; CI di repo ini
  hanya memakai SQLite dan tidak membutuhkan database.

## Troubleshooting

| Gejala                                             | Penyebab / solusi                                                         |
| -------------------------------------------------- | ------------------------------------------------------------------------- |
| `port is already allocated`                        | ubah port di `.env`                                                       |
| 500 `Connection refused` (Redis/DB) saat `serve`   | host di `.env` bukan nama service docker                                  |
| Browser memuat aset dari `0.0.0.0`                 | `public/hot` lama; restart service app                                    |
| `EACCES` di `~/.npm`                               | volume cache lama milik root: `docker compose down -v` lalu `./dev build` |
| Test Inertia: "page component file does not exist" | halaman React belum dibuat di `resources/js/pages/`                       |
