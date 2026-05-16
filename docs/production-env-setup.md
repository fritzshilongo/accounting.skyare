# Production Environment Setup

Use this guide to configure production safely without committing secrets.

## 1. Keep secrets out of Git

- Never commit `.env`, backup env files, passwords, or SSH keys.
- Keep only placeholder values in `.env.example`.
- Rotate any secret that was ever shared in chat or committed.

## 2. Recommended production `.env`

Use your hosting values and replace every placeholder below.

```env
APP_NAME="Skyare"
APP_ENV=production
APP_KEY=base64:REPLACE_WITH_APP_KEY
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_BASE_DOMAIN=your-domain.example

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=REPLACE_DB_NAME
DB_USERNAME=REPLACE_DB_USER
DB_PASSWORD=REPLACE_DB_PASSWORD

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=REPLACE_MAIL_HOST
MAIL_PORT=465
MAIL_USERNAME=REPLACE_MAIL_USER
MAIL_PASSWORD=REPLACE_MAIL_PASSWORD
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="no-reply@your-domain.example"
MAIL_FROM_NAME="Skyare"

APP_LICENSE_ISSUER_SUBDOMAIN=license
```

## 3. Production hardening checklist

- Set `APP_DEBUG=false`.
- Ensure web root points to `public`.
- Run `php artisan config:cache` and `php artisan route:cache`.
- Keep file permissions strict on `.env` and `storage`.
- Back up database before schema changes.

## 4. First deploy commands

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5. Post-deploy verification

- Confirm app login works.
- Confirm DB connectivity and migrations are current.
- Confirm queue/mail functions if enabled.
- Check logs for errors.
