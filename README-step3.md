# Step 3 - Design tokens + layout + components

Copy everything over the project (it overwrites `AppServiceProvider.php`, `DashboardController.php`, the login/dashboard views and the guest layout from step 2).

```bash
php artisan optimize:clear
php artisan serve
```
No npm/Vite step. Fonts and Font Awesome load from Google Fonts / cdnjs (needs internet; can be self-hosted later).

Includes: `public/css/app.css` (tokens + components), `public/js/app.js`, logo + favicon (cropped from the PNG logo),
`config/menu.php` (sidebar), `layouts/app|guest`, partials (sidebar, topbar), Blade components, custom pagination, `CLAUDE.md`.
