# APPLICATION ARCHITECTURE

## 1. Architecture Overview

The application uses a conventional Laravel monolithic architecture optimized for shared hosting.

Primary stack:

- Laravel backend.
- Blade server-rendered frontend.
- Tailwind CSS.
- Alpine.js.
- Chart.js.
- MySQL / MariaDB.

The architecture must remain simple, maintainable, and compatible with shared hosting.

---

## 2. High-Level Architecture

```text
Browser
   |
   v
Laravel Routes
   |
   v
Controllers
   |
   v
Services / Actions
   |
   v
Eloquent Models
   |
   v
MySQL / MariaDB
```

Frontend:

```text
Blade Templates
   |
   +-- Blade Components
   +-- Tailwind CSS
   +-- Alpine.js
   +-- Chart.js
```

---

## 3. Backend Layers

### Routes

Responsibilities:

- Map HTTP requests.
- Apply middleware.
- Keep route definitions concise.

Do not place business logic in route files.

---

### Controllers

Responsibilities:

- Receive requests.
- Call validation.
- Invoke domain/service logic.
- Return views or redirects.

Controllers should remain thin.

Avoid:

- Large calculations.
- Complex balance logic.
- Repeated financial logic.

---

### Form Requests

Use Laravel Form Request classes where validation becomes non-trivial.

Responsibilities:

- Validation.
- Authorization when appropriate.
- Input normalization when appropriate.

---

### Services / Actions

Use services/actions for critical business operations.

Examples:

```text
CreateTransactionAction
UpdateTransactionAction
DeleteTransactionAction
TransferFundsAction
CalculateAccountBalanceService
PayDebtAction
ContributeSavingGoalAction
```

Critical financial logic should not be duplicated across controllers.

---

### Models

Models represent database entities and relationships.

Models may contain:

- Relationships.
- Scopes.
- Casts.
- Simple domain helpers.

Avoid putting large orchestration logic inside models.

---

## 4. Financial Architecture

The financial ledger is the source of truth.

Account balance concept:

```text
opening_balance
+ total_income
- total_expense
+ incoming_transfers
- outgoing_transfers
- transfer_fees
- debt_payments
+ receivable_payments
- saving_goal_contributions
+ saving_goal_withdrawals
+/- adjustments
```

Do not trust frontend balance values.

Do not directly mutate account balances from user input.

If a cached balance is introduced in the future, it must be treated only as a derived optimization and must remain reconcilable from ledger data.

---

## 5. Transfer Architecture

Transfers must be atomic.

Pseudo flow:

```text
BEGIN DATABASE TRANSACTION

Validate ownership
Validate source != destination
Validate amount
Create transfer record
Apply source-side financial effect
Apply destination-side financial effect
Record fee if applicable

COMMIT
```

If any step fails:

```text
ROLLBACK
```

---

## 6. Authorization Architecture

Every user-owned model must be protected using ownership checks.

Preferred approaches:

- Policies.
- Scoped queries.
- Route model binding with authorization.
- Explicit user_id constraints.

Never load a financial record by ID alone and assume ownership.

Unsafe:

```php
Transaction::findOrFail($id);
```

Preferred pattern:

```php
$user->transactions()->findOrFail($id);
```

or an equivalent authorized policy approach.

---

## 7. Frontend Architecture

Use server-rendered Blade pages.

Structure example:

```text
resources/views/
├── layouts/
├── components/
├── dashboard/
├── accounts/
├── categories/
├── transactions/
├── transfers/
├── budgets/
├── bills/
├── debts/
├── saving-goals/
├── reports/
└── settings/
```

Use reusable components for:

- Buttons.
- Inputs.
- Selects.
- Modals.
- Cards.
- Tables.
- Badges.
- Empty states.
- Pagination.
- Alerts.

---

## 8. JavaScript Architecture

Use Alpine.js for lightweight interactivity.

Use Chart.js for financial charts.

Avoid building an SPA unless architecture is explicitly changed later.

Do not introduce React/Vue unnecessarily.

---

## 9. Asset Architecture

Development:

```bash
npm install
npm run dev
```

Production:

```bash
npm run build
```

Production server must only serve compiled assets.

Node.js runtime must not be required on shared hosting.

---

## 10. Database Architecture

Database:

- MySQL or MariaDB.
- InnoDB tables.
- Foreign keys where supported.
- DECIMAL for financial values.
- UTF8MB4 charset.

Primary schema source:

`SCHEMA.md`

Every database change must update:

1. Migration.
2. Model if needed.
3. `SCHEMA.md`.
4. Relevant validation.
5. Relevant tests if present.

---

## 11. File Storage

Transaction attachments may use Laravel storage.

Private transaction attachment storage:

```text
storage/app/private/transactions/{user_id}/
```

Attachments are delivered through an authenticated, ownership-authorized controller and are not published with `storage:link`. This works on shared hosting without exposing predictable public attachment URLs.

Uploads must validate:

- MIME type.
- File size.
- Extension.
- Ownership/access.

---

## 12. Error Handling

Financial operations must fail safely.

Never leave partial financial records.

Use:

- Database transactions.
- Validation exceptions.
- Authorization exceptions.
- Logging for unexpected failures.

Do not expose stack traces in production.

---

## 13. Logging

Production logging should capture:

- Unexpected exceptions.
- Failed critical financial operations.
- Security-related anomalies where appropriate.

Never log:

- Passwords.
- Session tokens.
- Database passwords.
- Secret keys.
- Full sensitive request payloads unnecessarily.

---

## 14. Deployment Architecture

Production target:

```text
Shared Hosting / cPanel
```

Expected runtime:

- PHP supported by current Laravel version.
- MySQL/MariaDB.
- Composer dependencies.
- Compiled frontend assets.

Avoid mandatory infrastructure such as:

- Redis.
- Docker.
- Supervisord.
- Long-running processes.
- WebSockets.
- Node.js production server.

---

## 15. Backup & Restore Architecture

The backup and restore system provides complete disaster recovery while maintaining strict financial data integrity:

```text
Backup Service Layer
   |
   +-- DatabaseDumper (mysqldump CLI detection with pure-PHP PDO fallback)
   +-- DatabaseRestorer (statement tokenizer with foreign key safeguards)
   +-- BackupService (ZIP packaging, manifest.json, SHA-256 checksums, retention)
   +-- RestoreService (Zip Slip validation, restore preview, pre-restore backup, rollback)
```

Core rules:
- Backup archive structure: `manifest.json`, `database/kasme.sql`, and `storage/private/...`.
- Storage directory: strictly private under `storage/app/private/backups/`.
- Exclusion: `.env`, `APP_KEY`, DB credentials, sessions, cache, logs, and compiled views are never included.
- Scheduler: scheduled backups execute via standard Laravel scheduler (`backup:scheduled`) without requiring daemons or external queue workers.
- Shared-hosting compatible: operates without shell access via the application-level PHP SQL export engine.

---

## 16. Architecture Change Policy

AI agents must not silently alter architecture.

Before introducing:

- New framework.
- New database.
- New major dependency.
- SPA conversion.
- Queue server.
- Cache infrastructure.
- Realtime server.
- New persistence mechanism.

The change must be explicitly approved and documented.
