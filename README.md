# Helper Home

Helper Home is a home-care and domestic-services platform. This repository preserves the existing static website at the repository root for its current Vercel deployment and adds the Laravel admin/API application under `backend/`.

## Repository layout

- Root HTML, CSS, JavaScript, images, and `api/`: static Helper Home website (Vercel document root)
- `backend/`: Laravel admin portal and API
- `docs/`: user, deployment, environment, backup, and UAT documentation

The backend and website have separate dependency manifests. Run commands from the relevant directory.

## Frontend website

The Vercel project currently deploys from the repository root. Keep the Vercel Root Directory set to `/` unless the deployment is intentionally migrated and tested.

```bash
npm ci
npm run build
npm run verify
```

The public enquiry handler is in `api/`. Copy `api/config.example.php` to `api/config.php` only for a local PHP environment; the real config is excluded from Git.

The website is configured to call the Laravel public API through its deployment configuration when the frontend and backend use different origins.

## Backend admin portal

Requirements: PHP 8.3+, Composer 2+, Node.js/npm, and SQLite or MySQL/MariaDB.

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve
```

The admin login is available at `/login`.

Useful commands:

```bash
php artisan migrate:status
php artisan route:list
php artisan optimize:clear
php artisan test
```

The Laravel web server document root must be `backend/public`. Do not expose `backend/`, `.env`, `vendor/`, `storage/`, or `database/` through the web server.

## Security and repository rules

- Never commit `backend/.env`, SMTP credentials, API secrets, private uploads, generated documents, or database files.
- `vendor/`, `node_modules/`, runtime files, private storage, local SQLite databases, SQL dumps, and archives are ignored.
- Backend private documents must remain on private storage and be served through authorized controllers.

See `docs/DEPLOYMENT.md`, `docs/BACKUP-RESTORE.md`, `docs/ENVIRONMENT.md`, and `docs/UAT-CHECKLIST.md` for operational guidance.
