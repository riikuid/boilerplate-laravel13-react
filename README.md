# Laravel + React Boilerplate

Backend boilerplate for new projects: **Laravel 13**, **Inertia 3**, **React 19**, **TypeScript**,
**Tailwind v4 + shadcn/ui**, **PostgreSQL**, **Redis**. Built on the official Laravel React starter kit
(Fortify auth, 2FA, Wayfinder) with a service-layer architecture and a complete reference module.

## Included

- **Auth**: login, reset password, email verification, two-factor, password confirmation (self-registration off; admins create users)
- **RBAC**: Spatie permission, permissions declared as PHP enums, generated TypeScript constants, `useCan()` / `<Can>`
- **Audit trail**: automatic model history + domain events + login activity, UI timeline and log page
- **UserStamp**: `created_by` / `updated_by` / `deleted_by` automatically
- **BusinessException**: one global handler → toast for Inertia, JSON for APIs
- **Server-side DataTable**: `TableQuery` (search, whitelisted sort, pagination) + React `DataTable`
- **Modules**: Users, Roles & Permissions, Audit Log (use them as the template for new modules)
- **Tooling**: Docker dev stack, Pint, Larastan level 7, `vp check` lint/format, `tsc`, PHPUnit, Vitest, GitHub Actions

## Quick start

```bash
./dev setup
./dev up -d
```

Open http://localhost:8300 and sign in with `admin@example.com` / `password` (local only).
Mail goes to Mailpit at http://localhost:8025.

## Everyday commands

```bash
./dev test                 # Pint + Larastan + PHPUnit
./dev npm run check        # frontend lint + format
./dev npm run types:check  # TypeScript
./dev npm test             # frontend tests (Vitest)
./dev artisan ...          # artisan inside the container
```

## Documentation

- [CONTRIBUTING.md](CONTRIBUTING.md): conventions (read first)
- [docs/new-module.md](docs/new-module.md): build a new module
- [docs/flow-system/rbac.md](docs/flow-system/rbac.md), [docs/flow-system/audit-trail.md](docs/flow-system/audit-trail.md)
- [docs/frontend.md](docs/frontend.md), [docs/development.md](docs/development.md)
- [docs/decisions.md](docs/decisions.md): why it is built this way

## Starting a new project from this boilerplate

1. Copy the folder (or use it as a template repository), `rm -rf .git && git init`.
2. Change `APP_NAME` and ports in `.env.example`; rename the compose project if needed.
3. `./dev setup && ./dev up -d`.
4. Remove what you do not need (e.g. the sample dashboard text) and add modules following `docs/new-module.md`.
