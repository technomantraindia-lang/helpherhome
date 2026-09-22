# Helper Home Backup and Restore

## Backup scope

Back up the database and all application data that cannot be recreated:

- Database dump
- `backend/storage/app/private`
- `backend/storage/app/public`
- Uploaded worker photos and documents
- Generated PDFs, signatures, stamps, logos, and QR assets
- Versioned document records and share-link records in the database

The pre-QA source/data backup created during Stage 12 is outside the project at:

`C:\Helpher home\stage12-backups\20260922-141240`

No `.env` file was copied into that backup.

## Suggested schedule

- Database: daily, retain at least 30 daily copies and periodic monthly copies.
- Application storage: daily incremental or synchronized backup.
- Keep an encrypted off-server copy and periodically test restoration.

## SQLite example

```bash
sqlite3 backend/database/database.sqlite ".backup 'helperhome-YYYYMMDD.sqlite'"
```

For MySQL, use the organization's approved `mysqldump` process and never place credentials in shell history or documentation.

## Restore procedure

1. Restore source from the release artifact.
2. Install Composer dependencies with `composer install --no-dev --optimize-autoloader`.
3. Restore the production environment file and existing `APP_KEY`.
4. Restore the database dump.
5. Restore `storage/app/private` and `storage/app/public`.
6. Run `php artisan storage:link` if the public link is missing.
7. Build static frontend assets with `npm ci` and `npm run build`.
8. Set ownership and permissions for `storage` and `bootstrap/cache` only.
9. Run `php artisan optimize:clear`, then cache config/routes/views after verification.
10. Test login, a private document download, a PDF, a payment receipt, and a website enquiry.
