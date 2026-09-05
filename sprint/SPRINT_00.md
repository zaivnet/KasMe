# SPRINT 00 — PROJECT FOUNDATION & INITIALIZATION

## Objective

Initialize the Personal Finance Manager project safely and consistently based on the existing project documentation.

This sprint is **foundation only**.

Do not build financial business modules yet.

The goal is to prepare a clean Laravel project structure that follows all documented rules and is ready for Sprint 01.

---

# MANDATORY FIRST STEP

Before creating, modifying, deleting, or generating any code, you MUST read all of these files completely:

1. `PRD.md`
2. `ARCHITECTURE.md`
3. `RULES.md`
4. `SCHEMA.md`
5. `DESIGN_SYSTEM.md`
6. `SECURITY.md`
7. `DEPLOYMENT.md`
8. `CHANGELOG.md`

These files are the project's source of truth.

Do not start implementation before understanding them.

If any instruction in this sprint conflicts with those documents:

- Do not silently override the documentation.
- Follow the safer documented rule.
- Report the conflict in the final sprint report.

---

# PROJECT TARGET

Application:

```text
Personal Finance Manager
```

Primary stack:

```text
Backend      : Laravel
Frontend     : Blade
CSS          : Tailwind CSS
Interactivity: Alpine.js
Charts       : Chart.js
Database     : MySQL / MariaDB
Production   : Shared Hosting / cPanel
```

Production must not require:

```text
Docker
Redis
MongoDB
Supervisor
PM2
WebSocket Server
Node.js runtime
```

Node.js may be used only for local development and frontend asset compilation.

---

# IMPORTANT AI RULES

You are working inside an AI-assisted software project.

Do not make speculative architectural decisions.

Do not create files simply because they are common in other projects.

Do not create duplicate implementations.

Do not create placeholder features.

Do not create fake APIs.

Do not create dummy data.

Do not create demo financial accounts.

Do not create demo users.

Do not create demo categories.

Do not create demo transactions.

Do not create test statistics on the dashboard.

Do not generate features outside this sprint.

Do not automatically continue to Sprint 01.

---

# SPRINT SCOPE

Sprint 00 includes only:

1. Inspect existing repository state.
2. Initialize Laravel if Laravel is not already initialized.
3. Verify project structure.
4. Configure base application settings.
5. Configure frontend build tooling.
6. Configure Tailwind CSS.
7. Configure Alpine.js.
8. Install/configure Chart.js dependency if appropriate.
9. Prepare base directory conventions.
10. Prepare `.env.example`.
11. Prepare shared-hosting-friendly defaults.
12. Add/update `.gitignore`.
13. Create minimal base layout structure.
14. Create a minimal application landing/auth shell only if necessary to verify rendering.
15. Verify application boot.
16. Verify frontend build.
17. Verify database configuration readiness.
18. Update `CHANGELOG.md`.
19. Produce sprint completion report.

No financial business feature should be implemented in this sprint.

---

# STEP 1 — INSPECT THE REPOSITORY

First inspect the complete repository.

Determine whether:

- Laravel already exists.
- `composer.json` exists.
- `package.json` exists.
- Vite is configured.
- Tailwind already exists.
- Alpine.js already exists.
- Chart.js already exists.
- Authentication already exists.
- Existing application code is present.

Do not overwrite a working project.

If Laravel is already initialized:

```text
DO NOT create a second Laravel project.
```

Adapt the existing project only where necessary.

---

# STEP 2 — LARAVEL INITIALIZATION

If the repository is empty and Laravel is not installed, initialize a current stable Laravel project compatible with the hosting environment.

Do not choose a Laravel version blindly.

Check:

- Local PHP version.
- Expected shared-hosting PHP compatibility.
- Composer compatibility.

Use standard Laravel structure.

Expected structure:

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/
tests/
vendor/
artisan
composer.json
package.json
vite.config.js
```

Do not introduce a custom framework structure.

---

# STEP 3 — APPLICATION CONFIGURATION

Configure sensible application defaults.

Application name:

```text
Personal Finance Manager
```

Default timezone:

```text
Asia/Jakarta
```

Default locale may remain:

```text
en
```

unless the current project explicitly uses Indonesian localization.

Do not hardcode environment-specific production secrets.

---

# STEP 4 — ENVIRONMENT TEMPLATE

Review and prepare:

```text
.env.example
```

It must contain only safe example values.

Required environment categories should include:

```text
APP_NAME
APP_ENV
APP_KEY
APP_DEBUG
APP_URL

APP_LOCALE
APP_FALLBACK_LOCALE

LOG_CHANNEL

DB_CONNECTION
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD

SESSION_DRIVER
CACHE_STORE
QUEUE_CONNECTION
```

Use shared-hosting-friendly defaults where reasonable.

Do not put:

- Real database credentials.
- Real passwords.
- API keys.
- Production secrets.

Do not commit `.env`.

---

# STEP 5 — DATABASE CONFIGURATION READINESS

Target database:

```text
MySQL / MariaDB
```

Ensure Laravel database configuration supports MySQL.

Do NOT create financial schema migrations in Sprint 00.

The tables described in `SCHEMA.md` will be implemented in later sprints.

Laravel framework default migrations may remain if required by Laravel or authentication architecture.

Do not create:

```text
accounts
categories
transactions
transfers
budgets
bills
debts
debt_payments
saving_goals
saving_goal_transactions
```

during Sprint 00.

---

# STEP 6 — FRONTEND STACK

Use:

```text
Blade
Tailwind CSS
Alpine.js
```

Do not introduce:

```text
React
Vue
Next.js
Nuxt
Inertia
Livewire
```

unless already explicitly required by project documentation.

If Tailwind is not configured, configure it correctly.

If Alpine.js is not installed, install/configure it.

The production build must work with:

```bash
npm run build
```

---

# STEP 7 — CHART.JS

Chart.js is the approved charting library.

It may be installed/configured in Sprint 00 so the dependency is ready for later dashboard sprints.

Do not create fake charts or dashboard statistics yet.

If installed, only verify that it can be imported successfully.

---

# STEP 8 — BASE VIEW STRUCTURE

Prepare a clean Blade structure.

Recommended:

```text
resources/views/
├── layouts/
│   ├── app.blade.php
│   └── guest.blade.php
├── components/
└── pages/
```

Do not over-engineer components.

Only create components that are actually required for the base shell.

Possible minimal shared components:

```text
application-logo
flash-message
form-error
```

Only if they are already needed.

---

# STEP 9 — BASE APPLICATION LAYOUT

Create a minimal base layout following `DESIGN_SYSTEM.md`.

The layout should support future:

- Sidebar.
- Topbar.
- Main content.
- Responsive mobile layout.
- Light/dark theme support.

However:

Do not build the final dashboard in Sprint 00.

Do not create fake financial cards.

Do not create fake charts.

Do not create fake transaction tables.

A simple application shell is enough.

---

# STEP 10 — CSS / DESIGN FOUNDATION

Establish reusable base styles aligned with `DESIGN_SYSTEM.md`.

Requirements:

- Clean typography.
- Consistent spacing.
- Responsive layout.
- Accessible focus states.
- Light/dark compatible structure.

Do not create a large custom CSS framework.

Prefer Tailwind utilities and reusable Blade components.

Do not introduce arbitrary visual systems outside the documented design system.

---

# STEP 11 — ROUTING

Keep routes minimal.

Sprint 00 may contain:

```text
/
```

and framework-required routes only.

If authentication is not part of Sprint 00, do not implement full authentication yet.

Do not create routes for:

```text
/accounts
/categories
/transactions
/transfers
/budgets
/bills
/debts
/saving-goals
/reports
```

Those belong to future sprints.

---

# STEP 12 — SECURITY FOUNDATION

Follow `SECURITY.md`.

Verify:

- `.env` is ignored.
- Debug mode is environment-controlled.
- CSRF middleware remains enabled.
- Blade escaping remains default.
- No secrets are committed.
- No unsafe public file structure is introduced.

Do not disable Laravel security features to make setup easier.

---

# STEP 13 — SHARED HOSTING COMPATIBILITY

Follow `DEPLOYMENT.md`.

The project must remain compatible with conventional cPanel hosting.

Do not introduce mandatory:

```text
queue worker daemon
Redis server
WebSocket server
Node.js server
Docker container
Supervisor
PM2
```

Production assets must be deployable after:

```bash
npm run build
```

---

# STEP 14 — .GITIGNORE

Ensure `.gitignore` excludes at minimum appropriate generated/private files such as:

```text
.env
/vendor
/node_modules
/public/build
/storage/*.key
```

Do not blindly ignore files that should be committed.

Follow Laravel defaults where appropriate.

---

# STEP 15 — README

Review the existing `README.md`.

If it is still the default Laravel README, replace or adapt it for this project.

README should contain only high-value information:

- Project name.
- Purpose.
- Stack.
- Requirements.
- Local installation.
- Environment setup.
- Database setup.
- Frontend build.
- Development command.
- Production build command.
- Documentation references.

Reference:

```text
PRD.md
ARCHITECTURE.md
RULES.md
SCHEMA.md
DESIGN_SYSTEM.md
SECURITY.md
DEPLOYMENT.md
```

Do not duplicate entire documentation inside README.

---

# STEP 16 — NO DUMMY DATA

This rule is absolute.

Do not create:

```text
factory-generated financial records
demo accounts
demo transactions
sample balances
sample charts
sample categories
fake dashboard values
```

If Laravel creates example factories by default and they are not required, they may remain unused or be removed only if safe.

Do not execute seeders that insert dummy application data.

---

# STEP 17 — TEST THE APPLICATION

Perform available verification.

At minimum verify:

## PHP

```bash
php artisan --version
```

## Laravel boot

```bash
php artisan about
```

or another safe framework health command.

## Routes

```bash
php artisan route:list
```

Confirm no unintended feature routes exist.

## Frontend dependencies

```bash
npm install
```

if necessary.

## Production asset build

```bash
npm run build
```

The build must complete successfully.

---

# STEP 18 — DATABASE SAFETY

Do not run destructive database commands against an unknown existing database.

Never automatically run:

```bash
php artisan migrate:fresh
php artisan db:wipe
php artisan migrate:refresh
```

unless explicitly instructed in a safe disposable environment.

If database configuration is not available, report that migrations were not executed.

---

# STEP 19 — CODE QUALITY CHECK

Before completing Sprint 00, inspect for:

- Duplicate files.
- Debug code.
- Broken imports.
- Broken Vite references.
- Missing assets.
- Unused temporary files.
- Unnecessary packages.
- Hardcoded secrets.
- Dummy data.
- Accidental business feature implementation.

Correct issues that are within Sprint 00 scope.

---

# STEP 20 — UPDATE CHANGELOG

Update:

```text
CHANGELOG.md
```

Under:

```text
[Unreleased]
```

Record only meaningful Sprint 00 changes.

Suggested entries:

```text
### Added
- Laravel project foundation.
- Tailwind CSS frontend foundation.
- Alpine.js integration.
- Chart.js dependency.
- Shared-hosting-ready environment structure.

### Changed
- Project README adapted for Personal Finance Manager.
```

Only include items actually implemented.

---

# DEFINITION OF DONE

Sprint 00 is complete only when:

- [ ] Documentation has been read.
- [ ] Existing repository has been inspected.
- [ ] Laravel project boots successfully.
- [ ] No duplicate Laravel project exists.
- [ ] MySQL/MariaDB is the intended database.
- [ ] Tailwind CSS is configured.
- [ ] Alpine.js is configured.
- [ ] Chart.js is available or dependency decision is documented.
- [ ] `npm run build` succeeds.
- [ ] `.env.example` is safe.
- [ ] `.env` is ignored.
- [ ] Base Blade layout exists if needed.
- [ ] No business financial module has been created.
- [ ] No dummy financial data exists.
- [ ] No fake dashboard data exists.
- [ ] No unnecessary dependency has been added.
- [ ] Project remains shared-hosting compatible.
- [ ] `README.md` accurately describes project setup.
- [ ] `CHANGELOG.md` is updated.
- [ ] Final sprint report is produced.

---

# STRICTLY FORBIDDEN IN SPRINT 00

Do NOT implement:

```text
Account CRUD
Category CRUD
Transaction CRUD
Transfer CRUD
Budget CRUD
Bill CRUD
Debt CRUD
Saving Goal CRUD
Financial reports
Dashboard financial calculations
PDF export
Excel export
Bank integrations
Notifications
Recurring transaction engine
```

Do not start authentication implementation unless an existing installation requires preserving it.

Authentication will be handled explicitly in a later sprint.

---

# FINAL SPRINT REPORT FORMAT

When implementation is complete, respond using this exact structure:

```md
# SPRINT 00 COMPLETION REPORT

## Status
COMPLETE / PARTIAL / BLOCKED

## Documentation Read
- PRD.md
- ARCHITECTURE.md
- RULES.md
- SCHEMA.md
- DESIGN_SYSTEM.md
- SECURITY.md
- DEPLOYMENT.md
- CHANGELOG.md

## Existing Repository State
Describe what existed before implementation.

## Files Created
- ...

## Files Modified
- ...

## Files Deleted
- ...

## Packages Added
### Composer
- ...

### NPM
- ...

## Database Changes
- None

or list only actual changes.

## Routes
- ...

## Commands Executed
- ...

## Verification Results
- Laravel boot: PASS / FAIL
- Route inspection: PASS / FAIL
- Frontend build: PASS / FAIL
- Shared hosting compatibility review: PASS / FAIL
- Dummy data check: PASS / FAIL

## Important Decisions
- ...

## Known Limitations
- ...

## Manual Actions Required
- ...

## Documentation Updated
- README.md
- CHANGELOG.md

## Ready for Sprint 01
YES / NO
```

---

# FINAL INSTRUCTION

Do not proceed beyond Sprint 00.

Do not implement future features proactively.

Preserve a clean, minimal foundation.

The next sprint must only begin after explicit user instruction.
