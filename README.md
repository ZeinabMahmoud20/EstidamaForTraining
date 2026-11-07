# Estidama Training Center - Step 1 (Database layer)

Generated from `training_center_erd.dbml` for **Laravel 11+ / MySQL 8**.
Contents: 59 migrations (+1 for foreign keys), 26 PHP Enums, 55 Models with relations.

## Install
```bash
composer create-project laravel/laravel estidama && cd estidama

# .env  ->  DB_CONNECTION=mysql, DB_DATABASE=estidama, ...
#           SESSION_DRIVER=file   CACHE_STORE=file   QUEUE_CONNECTION=sync

composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# copy app/ and database/ from this zip over the project (overwrite User.php)
rm database/migrations/0001_01_01_000000_create_users_table.php   # replaced by ours
php artisan migrate:fresh
```

## Notes
- `roles/permissions/role_permissions/user_roles` are replaced by spatie/laravel-permission (permission codes become permission names).
- `users.password_hash` -> `password`; login with `username`.
- All FKs live in the last migration (circular refs: users <-> trainers/trainees, ...).
- MySQL has no exclusion constraints: hall double-booking (`session_days`) is checked in a Service/FormRequest (Step 4).
- `enum` columns are MySQL ENUMs and cast to PHP Enums in the models.
- Regenerate any time: `python3 tools/dbml_to_laravel.py training_center_erd.dbml .`
