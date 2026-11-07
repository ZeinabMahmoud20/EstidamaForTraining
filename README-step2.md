# Step 2 - Auth + Roles/Permissions + Seeders

## Install
1. Copy everything in this zip over the project (overwrite when asked).
   Note: `app/Models/*` is the regenerated set (adds `HasFactory`) - safe to overwrite if you have not edited models yet.
2. `.env`:
   ```
   APP_LOCALE=ar
   APP_TIMEZONE=Africa/Cairo
   SEED_PASSWORD=choose-a-password      # default: password
   ```
3. Run:
   ```bash
   php artisan optimize:clear
   php artisan db:seed
   php artisan serve
   ```
4. Open /login. Users (local env): admin, manager, coordinator, admissions, halls, finance, quality.
   Only `admin` is created outside local.

## What is in it
- Login by `username` (+ remember, throttling 5 tries, inactive accounts blocked, `last_login_at` updated)
- 9 roles / 42 permissions (RolePermissionSeeder); `super_admin` bypasses all checks (Gate::before)
- Lookups: education levels, job grades, course types, the 9 criteria types
- 5 pre-programmed evaluation forms (system forms)
- Main branch + staff users; UserFactory, BranchFactory
