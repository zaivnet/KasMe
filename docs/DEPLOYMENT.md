# SHARED HOSTING DEPLOYMENT & INSTALLATION GUIDE

## 1. Deployment Target

Primary target:

- Conventional Shared Hosting / cPanel
- Web Server: Apache / LiteSpeed (with `mod_rewrite` enabled)
- Runtime: PHP 8.3+ (tested and verified on PHP 8.4)
- Database: MySQL 8.0+ / MariaDB 10.4+
- Protocol: HTTPS (SSL/TLS required for production cookies & financial data)

The production server does **NOT** require:

- Docker
- Redis / Memcached
- Supervisor / PM2
- WebSockets
- Long-running queue daemons (`QUEUE_CONNECTION=sync`)
- Node.js runtime or Vite dev server (Node.js is build-time only)

---

## 2. Server & PHP Requirements

Before deploying, verify that the shared hosting account provides:

1. **PHP Version**: `^8.3` (Laravel 13.x compatibility).
2. **Required PHP Extensions**:
   - `bcmath` (**MANDATORY**: used by KasMe for exact decimal financial calculations)
   - `ctype`
   - `curl`
   - `dom`
   - `fileinfo` (used for secure attachment MIME validation)
   - `mbstring`
   - `openssl`
   - `pdo`
   - `pdo_mysql`
   - `session`
   - `tokenizer`
   - `xml`
   - `zip` (**MANDATORY**: used by KasMe for Backup & Restore ZIP archives)
3. **Apache Modules**: `mod_rewrite`, `mod_negotiation`, `mod_headers`.

---

## 3. Production Asset Strategy

Node.js and Vite are build-time tools only.

Build assets on your local machine or CI before deployment:

```bash
npm install
npm run build
```

Verify that:

- `public/build/manifest.json` exists.
- Compiled CSS and JS exist in `public/build/assets/`.
- Do **NOT** upload `node_modules/` to shared hosting.
- `public/build` is uploaded with the release archive.

---

## 4. Composer Production Strategy

Install production dependencies locally or via hosting SSH:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

Do **NOT** run `composer update` in production.

If shared hosting does not provide Composer or SSH access:

1. Run `composer install --no-dev --prefer-dist --optimize-autoloader` in a local PHP 8.3+ environment.
2. Package and upload the resulting `vendor/` directory along with the application files.

---

## 5. Document Root & Directory Structure

### Preferred Architecture (Safe Isolation)

Place the application outside the public web root:

```text
/home/USERNAME/apps/kasme/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/                 <--- Web server Document Root
│   ├── build/
│   ├── .htaccess
│   ├── favicon.ico
│   ├── index.php
│   └── robots.txt
├── resources/
├── routes/
├── storage/
│   ├── app/private/        <--- Secure attachment storage
│   ├── framework/
│   └── logs/
├── vendor/
├── .env                    <--- Never accessible from web
├── artisan
└── composer.json
```

Configure the domain's **Document Root** in cPanel to:

```text
/home/USERNAME/apps/kasme/public
```

This prevents `.env`, database backups, logs, and application source code from being exposed via the web.

---

### Fallback: If cPanel Forces `public_html`

If your shared hosting provider does **NOT** allow changing the document root away from `public_html`:

1. Upload the entire project into a private directory outside `public_html`, e.g.:
   `/home/USERNAME/kasme_app/`
2. Move only the contents of `/home/USERNAME/kasme_app/public/` into `/home/USERNAME/public_html/`.
3. In `/home/USERNAME/public_html/index.php`, update the relative paths:

```php
// Register the Composer autoloader...
require __DIR__.'/../kasme_app/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../kasme_app/bootstrap/app.php';
```

4. **NEVER** blindly dump the entire Laravel project root into `public_html` without isolating public entry points.

---

## 6. Environment Configuration (`.env`)

Create the production `.env` from `.env.example`:

```env
APP_NAME="KasMe"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanel_user_kasme
DB_USERNAME=cpanel_user_kasme
DB_PASSWORD=YOUR_STRONG_DATABASE_PASSWORD

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true

FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_HOST=mail.your-domain.example
MAIL_PORT=587
MAIL_USERNAME=noreply@your-domain.example
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.example"
MAIL_FROM_NAME="${APP_NAME}"
```

**Security Rules:**
- `APP_DEBUG=false` must strictly be enforced.
- Never commit `.env` to version control.
- `SESSION_SECURE_COOKIE=true` ensures session cookies are transmitted only over HTTPS.

---

## 7. Storage, Permissions & Attachments

### Writable Directories

PHP processes must have write permissions to:

- `storage/`
- `storage/app/private/`
- `storage/framework/`
- `storage/framework/cache/`
- `storage/framework/sessions/`
- `storage/framework/views/`
- `storage/logs/`
- `bootstrap/cache/`

Recommended permission model:
- Directories: `755` (or `750` depending on host suEXEC/FastCGI configuration).
- Files: `644`.
- **Do NOT use `777`** unless explicitly required by an insecure host without alternatives.

### Private Attachments

Transaction attachments are stored in:

```text
storage/app/private/transactions/{user_id}/
```

- Attachments are served via an authenticated, ownership-checked controller endpoint (`/transactions/{transaction}/attachment`).
- **`php artisan storage:link` is NOT required** and should **NOT** be run for attachments.
- Files remain secure and cannot be accessed via direct URLs or by guessing IDs.

---

## 8. Database Migrations

Before running migrations:

1. Create a MySQL database and user in cPanel.
2. Grant `ALL PRIVILEGES` to the user for that database.
3. Configure `DB_*` in `.env`.
4. Ensure backups of any existing data are safely exported.

Run migrations using the non-interactive production flag:

```bash
php artisan migrate --force
```

**Rules:**
- **NEVER** run `php artisan migrate:fresh` or `php artisan migrate:refresh` on production.
- Do **NOT** run database seeders on production.

---

## 9. Laravel Optimization & Caching

After the final `.env` is saved:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Verify that all cache commands output success.

> **Testing Note:** During local automated testing (`php artisan test`), `config.php` should be cleared via `php artisan config:clear` so that test environment overrides in `phpunit.xml` (`APP_ENV=testing`, `DB_CONNECTION=sqlite`) take effect.

---

## 10. Cron & Queues

- **Scheduler / Cron**:
  If scheduled automated backups are enabled in **Pengaturan &rarr; Backup & Restore**, configure a standard cPanel Cron Job to run every minute.
  
  KasMe includes a runtime **Cron Command Generator** that inspects your installation path and configuration to display the exact, ready-to-use cron command in the **Panduan cPanel Cron Job** card.

  Example format:
  ```cron
  * * * * * cd '/home/USERNAME/apps/kasme' && '/opt/alt/php84/usr/bin/php' -d extension=bcmath.so -d extension=dom.so -d extension=fileinfo.so -d extension=mbstring.so -d extension=zip.so artisan schedule:run >> /dev/null 2>&1
  ```

  **Configuration Options in `.env`**:
  ```env
  # Optional: Specific PHP CLI Binary on Shared Hosting (defaults to current PHP_BINARY)
  KASME_PHP_CLI_BINARY=/opt/alt/php84/usr/bin/php

  # Optional: Comma-separated extension list for shared hosting environments
  KASME_PHP_CLI_EXTENSIONS=bcmath,dom,fileinfo,mbstring,zip
  ```

  The scheduler handles frequency (Daily, Weekly, Monthly), execution time, and idempotency automatically.

- **Queues**: Synchronous execution is used (`QUEUE_CONNECTION=sync`). A background queue worker daemon is **NOT REQUIRED**.

---

## 11. Step-by-Step Deployment Procedure

1. **Local Build**:
   - Run `npm run build` to generate `public/build/`.
   - Run `composer install --no-dev --optimize-autoloader`.
2. **Archive Release**:
   - Create a zip archive excluding `node_modules/`, local `.env`, `.git/`, and test caches.
3. **Upload to Hosting**:
   - Upload and extract into `/home/USERNAME/apps/kasme/`.
4. **Configure Web Root**:
   - Set domain Document Root to `/home/USERNAME/apps/kasme/public`.
5. **Configure Production `.env`**:
   - Copy `.env.example` to `.env` and fill in DB credentials, APP_URL, and APP_KEY.
   - If fresh installation: `php artisan key:generate`.
6. **Migrate Database**:
   - Execute `php artisan migrate --force`.
7. **Cache Bootstrap**:
   - Execute `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
8. **Verify HTTPS**:
   - Confirm SSL certificate is active (Let's Encrypt / cPanel AutoSSL).
9. **Perform Smoke Testing**:
   - Execute the 15-point smoke test checklist below.

---

## 12. Post-Deployment Smoke Test Checklist

- [ ] **Home & Login**: Navigating to domain root redirects cleanly to `/login` over HTTPS.
- [ ] **Authentication**: User can register (if enabled) and log in successfully.
- [ ] **Dashboard**: Dashboard loads with 4 summary cards, cash flow chart, category chart, and empty states without errors.
- [ ] **Accounts**: Create a new account with opening balance, verify balance display format (`Rp ...`).
- [ ] **Categories**: Create income and expense categories, verify icon and color badge rendering.
- [ ] **Transactions**: Record an income, record an expense, and verify account balance reconciliation.
- [ ] **Attachments**: Upload a receipt image, verify download via authenticated button, verify unauthorized users cannot access it.
- [ ] **Transfers**: Perform transfer between two accounts with fee; verify source and destination balances update atomically.
- [ ] **Budgets**: Set a monthly expense budget, verify progress percentage and over-budget calculation.
- [ ] **Bills**: Create an upcoming bill, verify recurrence badge and status.
- [ ] **Debts & Receivables**: Create debt record, record installment payment, verify remaining balance calculation.
- [ ] **Saving Goals**: Create target goal, contribute funds, verify balance reservation from linked account.
- [ ] **Reports & Export**: Filter reports by date/account, stream CSV export, export JSON personal backup from Settings.
- [ ] **Preferences**: Change theme (Light/Dark/System), currency, and date format; verify persistence.
- [ ] **Mobile Layout**: Verify navigation bar, FAB, and bottom sheets on real mobile device without overflow.
- [ ] **Logs**: Inspect `storage/logs/laravel.log` to confirm no warnings or uncaught exceptions occurred.

---

## 13. Backup & Rollback Plan

### Regular Backups
Regularly backup:
1. **Database**: MySQL dump via cPanel phpMyAdmin or `mysqldump`.
2. **Private Attachments**: Archive of `storage/app/private/transactions/`.
3. **Environment**: Secure off-site copy of `.env`.

### Rollback Procedure
If a release causes critical application issues:
1. **Application Code Rollback**:
   - Revert application files and `public/build` to the previous known-stable release archive.
   - Run `php artisan optimize:clear` and re-cache with `config:cache`, `route:cache`, `view:cache`.
2. **Database Rollback**:
   - Do **NOT** casually run `php artisan migrate:rollback` on production financial data.
   - For database corruption, restore the pre-deployment MySQL backup.
   - Restore private attachments if files were modified.

---

## 14. Instance Owner & Upgrade Strategy (Sprint 19.1+)

### Fresh Installation
1. Deploy KasMe and run `php artisan migrate --force`.
2. Ensure `ALLOW_REGISTRATION=false` in your `.env`.
3. Open the site in your browser and register the **first user account**.
4. The first registered user automatically becomes the **Instance Owner** (`is_instance_owner = true`).
5. Public registration automatically closes immediately after the first user is created.
6. Only the Instance Owner has access to **Backup & Restore Penuh** under *Settings*.

### Upgrading an Existing Installation
When migrating an existing database to Sprint 19.1+:
1. Run migrations:
   ```bash
   php artisan migrate --force
   ```
   - If the database has **exactly 1 user**, the migration automatically grants them `is_instance_owner = true`.
   - If the database has **multiple users**, the migration leaves all users unassigned to avoid guesswork.
2. Designate the Instance Owner explicitly via CLI:
   ```bash
   php artisan kasme:set-owner --email=your-admin@example.com
   ```
   (or execute `php artisan kasme:set-owner` without arguments for interactive selection).
3. Verify that `ALLOW_REGISTRATION=false` is present in `.env`.

---

## 15. Backup Archive Engine & Shared Hosting Fallback (Sprint 19.2)

### Architecture & Priority
KasMe uses an internal `ArchiveManager` abstraction that detects the available ZIP engine with the following priority:
1. **`ziparchive`**: Uses PHP's native `ZipArchive` extension when loaded in runtime.
2. **`cli_zip`**: Automatic fallback to system `zip` and `unzip` command-line binaries when `proc_open` or `exec` is permitted.
3. **`unavailable`**: If neither is accessible, operations fail safely with the explicit error:
   `"Server tidak memiliki ZipArchive maupun binary zip/unzip yang dapat digunakan."`

### Shared Hosting Compatibility (cPanel / CloudLinux / alt-php84)
On conventional shared hosting servers (such as `kas.selon.my.id`), the PHP CLI may support `zip` or CLI binaries, while the Apache/LiteSpeed web runtime lacks the `zip.so` extension.
- **No Host-Wide Configuration Changes Required**: KasMe will automatically fall back to CLI packaging without modifying global hosting configurations or disrupting neighboring accounts/domains.
- **Standard PKZIP Format**: Backups produced via CLI fallback are standard ZIP archives with relative paths (`manifest.json`, `database/kasme.sql`, `storage/private/...`), completely readable across Windows, Linux, macOS, and KasMe's restore subsystem.
- **Security & Integrity**:
  - Shell arguments are strictly escaped using `escapeshellarg()`.
  - The staging directory is strictly scoped and isolated.
  - Prior to any extraction, all entries are audited (`unzip -Z -1`) to prevent Zip Slip (`../`, absolute paths, forbidden files).
  - Extracted symlinks are automatically detected and purged.

### Troubleshooting
- **Error: `Server tidak memiliki ZipArchive maupun binary zip/unzip yang dapat digunakan`**:
  1. In cPanel, navigate to **Select PHP Version** &rarr; **Extensions** and check the `zip` box.
  2. Ensure `proc_open` and `exec` are not listed in `disable_functions` under PHP Options.
  3. Check that `/usr/bin/zip` and `/usr/bin/unzip` are executable in your cPanel user shell.
