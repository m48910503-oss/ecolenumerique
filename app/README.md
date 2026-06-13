# MVC Migration (Progressive)

This project started as page-based PHP scripts.  
The migration strategy is progressive to avoid breaking production features.

## Structure

- `app/Core/` : base MVC classes (`Controller`, `View`).
- `app/Core/RouteDispatcher.php` : route normalizer + dispatcher.
- `app/Controllers/` : request handlers.
- `app/Views/` : templates.
- `app/bootstrap.php` : autoloader for `App\\` classes.
- `app/routes.php` : route table (`path -> controller`).

## Entry Point

The application now uses a single front controller:

- `index.php` boots framework dependencies.
- It dispatches routes from `app/routes.php`.
- Legacy wrapper files were removed.
- Old URLs with `.php` are still supported by path normalization.

## How to migrate next pages

1. Add controller/service/model for the feature.
2. Register the page route in `app/routes.php`.
3. Move HTML to `app/Views/...`.
4. Keep helpers/DB from `includes/bootstrap.php` during transition.

## Smoke checks

Run a quick routing smoke test from project root:

`php app/tests/smoke_routes.php`

## Payments ops

FedaPay pending reconciliation (CLI):

`php app/cron/reconcile_fedapay_pending.php --limit=100 --age-minutes=5`

## Security ops

Support attachment migration to private storage:

`php app/cron/migrate_support_attachments.php`

Targeted security guards:

- `php app/tests/login_throttle_functional.php`
- `php app/tests/admin_bootstrap_route_guard.php`
- `php app/tests/certificate_privacy_guard.php`
- `php app/tests/support_storage_private_path_functional.php`

Deployment checklist:

`SECURITY_DEPLOYMENT.md`
