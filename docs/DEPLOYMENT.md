# Helper Home Deployment Guide

## Requirements

- PHP 8.3 or newer
- Composer 2.x
- Laravel 13
- A supported database (MySQL/MariaDB or SQLite for controlled environments)
- Node.js/npm for frontend asset builds
- HTTPS termination and a web server whose Laravel document root is `backend/public`

## Frontend deployment

The frontend remains static HTML/CSS/JavaScript. Run `npm ci` and `npm run build` in `frontend`, then deploy the static directory to the chosen host. Set `window.HELPER_HOME_API_BASE_URL` or `HelperHomeConfig.apiBaseUrl` to the deployed Laravel API base when frontend and backend use different origins. Configure the backend CORS allowlist to match the exact frontend origin.

## Backend deployment

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not run `migrate:fresh`, `db:wipe`, destructive SQL, or force-push history.

## Web server and files

- Point the Laravel virtual host only to `backend/public`.
- Keep `.env`, `vendor`, `storage`, `database`, and source files outside the public document root.
- Make only `storage` and `bootstrap/cache` writable by the web process.
- Keep generated PDFs, signatures, stamps, registration documents, and worker identity documents on the private disk and serve them through authorized controllers.
- Keep worker profile photos on the public disk only when intentionally used by the UI.

## Post-deployment checks

```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan test
```

Then complete the UAT checklist, verify the login, submit a website enquiry, download a private document, and inspect the logs for errors.

## Queue and scheduler

No application feature currently requires a queue worker or scheduler to complete a request. If email, notifications, cleanup, or scheduled backups are introduced, configure a supervised worker and the host's scheduler explicitly. Do not guess a server path; use the deployed absolute path in the cron entry.
