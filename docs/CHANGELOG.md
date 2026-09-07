# CHANGELOG

All notable changes to this project should be documented in this file.

The format is inspired by Keep a Changelog principles.

---

## [Unreleased]

- `ArchiveManager` abstraction supporting multi-engine ZIP packaging and decompression with automatic priority (`ziparchive` -> `cli_zip` -> `unavailable`).
- Automatic CLI `zip` and `unzip` fallback for shared hosting environments (e.g. cPanel / CloudLinux alt-php84 on `kas.selon.my.id`) where PHP's `ZipArchive` extension is missing in the web runtime.
- Pre-extraction entry audit via `unzip -Z -1` preventing Zip Slip, path traversal, and sensitive file overwrite before decompression commands execute.
- Post-extraction symlink abuse detection and automatic purge protection.
- Backward-compatible optional `archive_engine` metadata field in backup `manifest.json`.
- Single Instance Owner security model (`users.is_instance_owner`) with atomic first-user bootstrap and CLI owner assignment command (`php artisan kasme:set-owner`).
- Centralized `manage-system-backups` authorization Gate protecting all backup and restore operations from non-owner users.
- Public registration toggle (`ALLOW_REGISTRATION`) via `config/kasme.php`, automatically restricting registrations once the instance owner exists.
- Defensive zero-denominator guards on `Budget::utilizationPercentage()` and `SavingGoal::progressPercentage()`.
- Explicit mandatory PHP extension declarations in `composer.json` (`ext-bcmath`, `ext-zip`).
- Production-ready Backup & Restore system supporting manual full backups, secure downloads, history, and destructive restore.
- Full backup archive ZIP packaging with `manifest.json`, `database/kasme.sql`, and private storage attachments (`storage/app/private/`).
- Dual database dump engine: prioritizing CLI `mysqldump` with seamless fallback to pure-PHP PDO dumper supporting MySQL/MariaDB and SQLite.
- Safe multi-step restore flow with archive validation, diagnostic preview, explicit confirmation (`RESTORE`), mandatory pre-restore automatic backup, maintenance locking, and safe rollback recovery.
- Scheduled backups via Laravel Scheduler (`backup:scheduled`) with configurable frequency (daily, weekly, monthly), execution time, and retention policies (7, 14, 30, all).
- Zip Slip and path traversal protection during archive inspection and extraction.
- SHA-256 payload integrity checksum validation before restore.
- Secret exclusions: `.env`, `APP_KEY`, database credentials, cache, logs, and sessions are strictly excluded from backup archives.
- Mobile-responsive and dark-mode-ready Backup & Restore dashboard under Settings.
- Android-style mobile bottom navigation, floating transaction action, and an Alpine.js “Lainnya” sheet.
- Reusable inline icon, icon picker, color picker, toast, confirmation dialog, and submit-loading behavior.
- Mobile bottom-sheet report filters and safe-area-aware app shell.
- Private transaction attachments with validated upload, replacement, removal, and ownership-protected download.
- Filter-aware streamed CSV exports for transactions and financial reports.
- Streamed personal JSON backup export without passwords, tokens, secrets, or attachment contents.
- Per-user settings for default currency, date format, timezone, and light/dark/system theme.
- Request-level preference application with safe lazy settings creation.
- Financial reports with daily, weekly, monthly, yearly, and custom date periods.
- Account, category, and transaction-type report filters with paginated ledger detail.
- SQL-aggregated income, expense, net cash flow, category, and account breakdowns.
- Saving goal CRUD with contribution/withdrawal history, progress calculation, automatic completion, and cancellation.
- Atomic saving-goal movements with canonical linked-account balance effects and safe withdrawal limits.
- Debt and receivable CRUD with payment history, remaining-balance reconciliation, due dates, and derived statuses.
- Atomic debt-payment creation, editing, and reversal with canonical account balance effects.
- User-owned bill CRUD with due dates, recurrence metadata, payment status, filtering, and soft-delete archival.
- Safely derived overdue presentation and a real-data upcoming-bills dashboard widget.
- Monthly expense-category budget CRUD with month/year filtering and duplicate-period protection.
- Real-time budget utilization, remaining amount, progress, over-budget status, and dashboard summary derived from expense transactions.
- Real-data financial dashboard with monthly metrics, account summaries, and recent transactions.
- Chart.js income-versus-expense and expense-by-category visualizations with empty states.
- Atomic user-owned account transfers with fees, history, editing, and soft-delete reversal.
- Transfer-aware account balance reconciliation without duplicate transaction effects.
- User-owned income, expense, and adjustment transaction ledger with filters and pagination.
- Transaction create/update/delete actions and soft-delete behavior.
- Server-side account balance reconciliation from opening balances and transaction effects.
- User-owned income and expense category CRUD with optional parent-child hierarchy.
- Category type/status filters and safe disable behavior.
- Category ownership, compatible-parent, and circular-hierarchy validation.
- User-owned financial account CRUD with opening balances and calculated balance display.
- Account ownership policy, validation requests, and soft-delete archive behavior.
- Responsive account list, detail, create, and edit pages.
- Secure registration, login, logout, and password reset flows.
- Authenticated application shell and guest authentication pages.
- Profile information and password management.
- Authentication and ownership convention documentation.
- Laravel 13 project foundation and shared-hosting-ready environment template.
- Tailwind CSS, Alpine.js, and Chart.js frontend foundation.
- Responsive Blade application and guest layouts with light/dark-compatible styling.
- Initial project documentation.
- Product requirements definition.
- Application architecture definition.
- AI coding rules.
- Database schema definition.
- Design system.
- Security requirements.
- Shared hosting deployment guidelines.

### Changed

- Replaced `(float)` cast with strict `BigDecimal::isZero()` in `Debt::effectiveStatus()`.
- Optimized `SavingGoalController::show()` and `edit()` by utilizing `loadProgress()` instead of redundant database queries.
- Completed Sprint 18 final production readiness audit and verified shared-hosting / cPanel deployment workflow.
- Audited PHP 8.3+ compatibility, verified BCMath requirement for decimal financial calculations, and verified all core extensions (ctype, curl, dom, fileinfo, mbstring, openssl, pdo, pdo_mysql, session, tokenizer, xml).
- Updated `.env.example` with production-oriented keys, strict `APP_DEBUG=false`, secure cookie configuration, file session/cache, and sync queues.
- Enhanced `docs/DEPLOYMENT.md` with verified cPanel document root structure, safe `public_html` fallback, asset deployment flow, post-deployment smoke testing checklist, and database rollback safety.
- Verified production caching commands (`config:cache`, `route:cache`, `view:cache`) and simulated application boot under `APP_ENV=production`.
- Polished authenticated and guest shells, dashboard metrics, account cards, transaction and transfer forms, budgets, debts, settings, dark mode, focus states, and responsive spacing.
- Completed the Sprint 16 local deployment preflight and documented the remaining cPanel, production database, SSL, permission, and smoke-test blockers.
- Clarified that private transaction attachments do not use `storage:link`.
- Verified production dependency installation, optimized autoloading, Laravel configuration/route/view caches, and compiled Vite assets for shared-hosting release compatibility.
- Aggregated incoming and outgoing transfer effects in SQL during account-balance reconciliation to avoid loading full transfer histories into memory.
- Updated README and deployment guidance for production cache commands, private storage, synchronous queues, HTTPS cookies, and shared-hosting release steps.
- Account balance reconciliation now treats saving-goal contributions as reserved outflows and withdrawals as returned inflows.
- Account balance reconciliation now includes debt payments as outflows and receivable payments as inflows.
- Replaced the default Laravel README with project-specific setup and deployment guidance.

### Security

- Completed the Sprint 14 authorization and financial-integrity audit, including combined-ledger reconciliation and ownership attack coverage.
- Disabled Laravel's generic local-disk serving routes so private attachments are available only through the authorized transaction endpoint.
- Stored transaction attachments outside the public directory using generated filenames and authenticated ownership checks.
- Scoped every CSV and JSON export to the authenticated user and excluded credential/session fields.
- Enforced saving goal, movement, and linked-account ownership throughout all goal operations.
- Enforced debt, payment, and selected-account ownership across all debt workflows.
- Enforced bill ownership and same-user optional-category validation on all bill writes.
- Enforced budget ownership and same-user expense-category validation on every budget write.
- Enforced transfer ownership and same-user source/destination account validation.
- Enforced transaction, account, and category ownership validation on all ledger writes.
- Enforced ownership checks for category access and parent selection.
- Enforced authenticated ownership authorization for every account detail, edit, update, and archive action.
- Protected application and profile routes with authentication middleware.
- Protected authentication routes with guest middleware and request throttling.
- Added session regeneration, CSRF-protected logout, framework password hashing, and current-password validation.

### Fixed

- None.

### Removed

- None.

---

# CHANGELOG RULES

Use the following categories:

```text
Added
Changed
Deprecated
Removed
Fixed
Security
```

Example:

```md
## [0.2.0] - 2026-08-20

### Added
- Monthly budget module.
- Budget utilization chart.

### Changed
- Transaction filter now supports date range.

### Fixed
- Corrected transfer fee calculation.

### Security
- Added authorization policy to transaction attachments.
```

---

# DOCUMENTATION POLICY

Update this changelog when a sprint introduces:

- New module.
- Database schema change.
- Architecture change.
- Important UI behavior change.
- Security change.
- Deployment requirement.
- Breaking change.
- Major bug fix.

Do not fill the changelog with trivial formatting-only edits unless they are operationally relevant.

---

# VERSIONING

Suggested project versioning:

```text
0.x.x = active development
1.0.0 = first stable production release
```

Recommended semantic pattern:

```text
MAJOR.MINOR.PATCH
```

Where:

- MAJOR = incompatible/breaking changes.
- MINOR = backward-compatible features.
- PATCH = backward-compatible fixes.
