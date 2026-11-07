# Estidama Training Center (استدامة للتدريب والتطوير)

Training-center management system. Arabic only, RTL. Laravel (MVC, Blade) + MySQL 8. **No Filament, no Vite/npm needed** (plain CSS/JS in `public/`).

## Stack & conventions
- Controllers / FormRequests / Policies / Blade views per module; routes in `routes/web.php`, resource-style named routes (`courses.index`, ...).
- Auth: login by `username` (column `password`), roles & permissions via spatie/laravel-permission. Permission codes are UPPER_SNAKE (`CHANGE_HALL`, `APPROVE_NOMINATION`). `super_admin` bypasses via `Gate::before`.
  Protect routes with `->middleware('can:CODE')` and views with `@can('CODE')`.
- DB: schema comes from `training_center_erd.dbml` through `tools/dbml_to_laravel.py` (migrations, Enums in `app/Enums`, Models with relations). Do not hand-edit generated migrations: add new migrations instead.
  FKs live in `2026_01_01_009999_add_foreign_keys.php`. Enum columns are cast to PHP Enums.
- MySQL has no exclusion constraints: hall double-booking (`session_days`) must be validated in a Service.
- Seeders are idempotent; `DemoUsersSeeder` creates demo staff users in `local` only. Factories: add one per module with its tests.
- UI text is Arabic; code, routes, class names are English.

## Design system (tokens in `public/css/app.css`, derived from the logo)
- Primary blue `--brand #1d5a9c` / `--brand-deep #14406f`; accent orange `--accent #e8802f`, gold `--accent-gold #e8a030` (accent = borders, focus ring, active marker; never as text color on white).
- Light first, calm and official: radius 6/10/14px, soft blue shadows, no gradients/glass/neon, zebra tables with blue header.
- Fonts: Tajawal (headings), IBM Plex Sans Arabic (body), Inter (numbers/latin). Icons: Font Awesome 6.5.2.
- Use logical CSS properties (`margin-inline-start`, ...). Logo: `public/images/logo.png` (PNG only for now; replace with SVG when available, do not recolor).
- Accessibility: visible focus, font resize buttons in the topbar, reduced-motion support.

## UI building blocks
Layouts: `layouts.app` (sidebar + topbar + flash alerts), `layouts.guest`.
Components: `x-page-header`, `x-card` (slot `actions`), `x-stat`, `x-badge`, `x-alert`, `x-empty`, `x-table`,
`x-form.input|select|textarea|checkbox` (handle `old()`, errors, required mark).
Sidebar is driven by `config/menu.php`: an item appears when the user has ANY listed permission and renders as "قريبًا" until its named route exists, so a new module only needs to register its routes.
Delete buttons: `<form method="POST" data-confirm="...">` (confirmed in `public/js/app.js`).

## Module pattern (repeat per module)
Routes -> `XController` (index/create/store/show/edit/update/destroy) -> `StoreXRequest`/`UpdateXRequest` -> `XPolicy` if row-level rules -> views `resources/views/x/{index,create,edit,show}.blade.php` -> factory + feature test.
Finish one module completely before the next. Return only changed files.
