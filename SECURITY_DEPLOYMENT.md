# Security Deployment Checklist

This file captures the minimum post-fix deployment steps for the hardened build.

## 1. Rotate compromised Google secrets

Before deploying, revoke and replace:

- the previous Google service account private key
- the previous Google OAuth refresh token

Do not reuse the values that were previously stored under `storage/drive/`.

## 2. Required environment variables

Set these on the server, outside the repository:

```txt
APP_ENV=production
ADMIN_BOOTSTRAP_REGISTER=0
ADMIN_REGISTER_SECRET=________________________
APP_DRIVE_SERVICE_ACCOUNT_JSON=C:\secrets\google\service-account.json
APP_DRIVE_OAUTH_TOKEN_PATH=C:\secrets\google\oauth_token.json
APP_SUPPORT_STORAGE_DIR=C:\xampp\app-private\ecolenumerique\support
```

Notes:

- `APP_DRIVE_SERVICE_ACCOUNT_JSON` and `APP_DRIVE_OAUTH_TOKEN_PATH` must point to files outside `htdocs` and outside the repository.
- `APP_SUPPORT_STORAGE_DIR` should point to a private directory that is not web-accessible.
- The super-admin registration link becomes `https://votre-domaine/admin/register?token=...` once `ADMIN_REGISTER_SECRET` is set.

## 3. Filesystem layout

Recommended Windows layout:

```txt
C:\xampp\
  app-private\
    ecolenumerique\
      support\

C:\secrets\
  google\
    service-account.json
    oauth_token.json
```

Recommended permissions:

- support directory: application account only
- Google secrets: application account only

## 4. HTTP checks after deployment

Validate these manually:

- `/admin/register` returns `404` in production
- repeated login failures trigger throttling
- support uploads reject disallowed file types
- company SSO rejects users without an existing membership or invite flow
- certificates no longer request `api.qrserver.com`

## 5. Useful CLI checks

```txt
C:\xampp\php\php.exe app\tests\login_throttle_functional.php
C:\xampp\php\php.exe app\tests\admin_bootstrap_route_guard.php
C:\xampp\php\php.exe app\tests\certificate_privacy_guard.php
C:\xampp\php\php.exe app\tests\support_storage_private_path_functional.php
```

## 6. Legacy support attachments migration

Dry run:

```txt
C:\xampp\php\php.exe app\cron\migrate_support_attachments.php
```

Apply changes:

```txt
C:\xampp\php\php.exe app\cron\migrate_support_attachments.php --commit
```
