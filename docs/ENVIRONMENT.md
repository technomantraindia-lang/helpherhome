# Helper Home Environment Variables

Required names for a production deployment. Secret values are intentionally omitted.

## Application

- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_DEBUG`
- `APP_URL`

## Database

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## Storage, cache, session, and queue

- `FILESYSTEM_DISK`
- `CACHE_STORE`
- `SESSION_DRIVER`
- `SESSION_LIFETIME`
- `SESSION_SECURE_COOKIE`
- `SESSION_HTTP_ONLY`
- `SESSION_SAME_SITE`
- `QUEUE_CONNECTION`

## Mail and AWS (when used)

- `MAIL_MAILER`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_DEFAULT_REGION`
- `AWS_BUCKET`
- `AWS_URL`
- `AWS_ENDPOINT`
- `AWS_USE_PATH_STYLE_ENDPOINT`

## Static frontend integration

- `FRONTEND_URL`
- `FRONTEND_ALLOWED_ORIGINS`
- `HELPER_HOME_API_BASE_URL` (frontend deployment configuration, if frontend and API use different origins)
