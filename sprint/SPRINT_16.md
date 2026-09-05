# SPRINT 16 — SHARED HOSTING / CPANEL DEPLOYMENT

## Objective

Prepare and execute the final deployment procedure for conventional shared hosting/cPanel without requiring persistent Node.js or daemon services.

---

# MANDATORY FIRST STEP

Before modifying code, read completely:

1. `PRD.md`
2. `ARCHITECTURE.md`
3. `RULES.md`
4. `SCHEMA.md`
5. `DESIGN_SYSTEM.md`
6. `SECURITY.md`
7. `DEPLOYMENT.md`
8. `CHANGELOG.md`

Also inspect all completed previous sprints.

These documents are the source of truth.

Do not silently change architecture, schema, dependencies, or financial rules.

Absolute rules:

- No dummy data.
- No fake dashboard values.
- No duplicate modules.
- No unrelated refactors.
- No destructive database commands.
- Preserve shared-hosting compatibility.
- Do not automatically continue to the next sprint.

---

# FINAL DEPLOYMENT SPRINT

Follow `DEPLOYMENT.md` strictly.

---

# PRE-DEPLOYMENT

Verify:

- Production PHP version compatibility.
- Required PHP extensions.
- MySQL/MariaDB availability.
- SSL/HTTPS.
- Domain/subdomain.
- Document root options.
- Composer availability or local vendor build strategy.
- SSH availability if relevant.

Do not assume features the hosting provider does not have.

---

# BUILD

Production preparation:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

Do not upload:

```text
node_modules
.git
development secrets
local .env
```

Upload required compiled assets and vendor strategy appropriate to hosting.

---

# DOCUMENT ROOT

Preferred:

```text
domain document root -> project/public
```

If hosting forces `public_html`, use the safe adaptation documented in `DEPLOYMENT.md`.

Never expose `.env`.

---

# PRODUCTION ENV

Configure:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://...
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
```

Use actual hosting credentials only in `.env`, never source files.

---

# DATABASE

Before migration:

- Backup existing database.
- Review pending migrations.

Run only when safe:

```bash
php artisan migrate --force
```

Never use:

```bash
migrate:fresh
db:wipe
migrate:refresh
```

on production.

---

# STORAGE

Verify:

```text
storage/
bootstrap/cache/
```

are writable.

Attempt:

```bash
php artisan storage:link
```

only if attachment architecture uses it and host permits symlink.

Use documented fallback if necessary.

---

# CACHE

Run as appropriate:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

# POST-DEPLOYMENT TEST

Verify production:

1. Login.
2. Logout.
3. Account creation.
4. Category creation.
5. Income transaction.
6. Expense transaction.
7. Transfer.
8. Balance reconciliation.
9. Dashboard metrics.
10. Budget calculation.
11. Bill view.
12. Debt/receivable flow.
13. Saving goal.
14. Reports.
15. Settings.
16. Attachments if enabled.
17. Export if enabled.
18. Mobile responsiveness.
19. HTTPS.
20. Cross-user authorization.

---

# PRODUCTION SAFETY

Confirm:

```text
APP_DEBUG=false
.env inaccessible
no directory listing
no dummy data
no debug routes
no dev server dependency
no exposed logs
```

---

# FINAL RELEASE REPORT

Respond with:

```md
# SPRINT 16 DEPLOYMENT REPORT

## Status
DEPLOYED / PARTIAL / BLOCKED

## Hosting Environment
- Provider:
- PHP:
- Database:
- Document Root:
- SSL:

## Build
- Composer production install:
- npm build:

## Database
- Backup:
- Migrations:

## Storage
- Permissions:
- Storage link/fallback:

## Cache
- Config cache:
- Route cache:
- View cache:

## Production Verification
- Authentication:
- Accounts:
- Categories:
- Transactions:
- Transfers:
- Dashboard:
- Budgets:
- Bills:
- Debts:
- Saving Goals:
- Reports:
- Settings:
- Attachments:
- Export:
- Mobile:
- HTTPS:
- Authorization:

## Security Checks
- APP_DEBUG=false:
- .env protected:
- Dummy data absent:
- Debug routes absent:

## Known Issues
- ...

## Final Status
READY FOR PRODUCTION / NOT READY
```

Do not make further feature changes in this sprint unless required to fix a deployment blocker.
