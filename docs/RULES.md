# PROJECT RULES

## 1. Source of Truth

Before generating or modifying code, read:

1. `PRD.md`
2. `ARCHITECTURE.md`
3. `RULES.md`
4. `SCHEMA.md`
5. `DESIGN_SYSTEM.md`
6. `SECURITY.md`
7. `DEPLOYMENT.md`

These documents are the source of truth.

If an instruction conflicts with these documents, do not silently change the architecture or schema.

Report the conflict before implementing a structural change.

---

## 2. General Rules

- Follow the documented architecture.
- Do not invent unnecessary modules.
- Do not create duplicate features.
- Do not create duplicate controllers, services, models, or routes.
- Do not overwrite working implementations unnecessarily.
- Do not refactor unrelated code during a focused sprint.
- Do not create placeholder features unless explicitly requested.
- Do not create dummy data.
- Do not create demo accounts.
- Do not create demo transactions.
- Do not add sample statistics to production pages.
- Do not leave debug code in production.
- Do not leave TODO implementations that pretend to be complete.

---

## 3. Technology Rules

Approved stack:

- Laravel.
- PHP.
- Blade.
- Tailwind CSS.
- Alpine.js.
- Chart.js.
- MySQL / MariaDB.

Production target:

- Conventional shared hosting.
- cPanel-compatible environment.

Do not require:

- Docker.
- Redis.
- MongoDB.
- PM2.
- Supervisor.
- WebSocket server.
- Node.js runtime in production.

Node.js is only permitted for local development and asset compilation.

---

## 4. Database Rules

`SCHEMA.md` is the source of truth for database design.

Never:

- Create undocumented tables.
- Create undocumented columns.
- Rename columns without updating schema documentation.
- Change column meaning silently.
- Remove columns without explicit instruction.
- Use FLOAT or DOUBLE for money.

Use:

```text
DECIMAL(18,2)
```

for monetary values unless another precision is explicitly documented.

Every migration must:

- Have a valid `up()`.
- Have a valid `down()`.
- Preserve data integrity.
- Use indexes where appropriate.
- Use foreign keys where appropriate.

---

## 5. Financial Integrity Rules

Financial integrity has higher priority than UI convenience.

Every monetary movement must leave a persistent financial record.

Do not trust:

- Frontend totals.
- Frontend balances.
- Hidden form balance fields.
- JavaScript-calculated authoritative values.

Backend is the source of truth.

Account balance must derive from financial records.

---

## 6. Transaction Rules

Supported transaction types:

- income
- expense
- adjustment

Rules:

- Store transaction amount as a positive number.
- Transaction type determines direction.
- Income increases balance.
- Expense decreases balance.
- Adjustment direction must be explicitly represented.
- Every transaction belongs to one authenticated user.
- Every transaction must belong to an account.
- Category may be nullable only where documented.
- Transactions must not be hard-deleted by default.

Use soft deletes where applicable.

---

## 7. Transfer Rules

Transfers must satisfy:

```text
from_account_id != to_account_id
```

Source financial effect:

```text
-(amount + fee)
```

Destination financial effect:

```text
+amount
```

Transfers must use a database transaction.

Never create the destination-side record if the source-side operation fails.

Never permit partial transfer completion.

---

## 8. Account Rules

Accounts belong to one user.

Opening balance:

- May be supplied when account is created.
- Must not be casually editable afterward.
- Corrections should use explicit adjustment records where practical.

Account deletion must not silently destroy financial history.

Prefer:

- Soft delete.
- Archive.
- Deactivate.

---

## 9. Authorization Rules

All user-owned queries must enforce ownership.

Never allow:

- User A to view User B's transaction.
- User A to edit User B's account.
- User A to access another user's attachment by guessing an ID or URL.

Use policies or properly scoped queries.

---

## 10. Validation Rules

All write operations require server-side validation.

Validate at minimum:

- Ownership.
- Amount.
- Dates.
- Account.
- Category.
- Transaction type.
- Transfer source.
- Transfer destination.
- File attachment.
- Status transitions.

Frontend validation may improve UX but is never sufficient by itself.

---

## 11. Security Rules

Never expose:

- `.env`
- passwords
- API keys
- database credentials
- application secrets
- session tokens

Never commit secrets into source control.

Use Laravel security mechanisms:

- CSRF protection.
- Password hashing.
- Authentication middleware.
- Authorization policies.
- Escaped Blade output by default.

---

## 12. UI Rules

Follow `DESIGN_SYSTEM.md`.

Do not introduce random:

- Colors.
- Typography.
- Spacing.
- Button styles.
- Card styles.
- Icons.

Pages must be responsive.

Minimum target:

- Mobile.
- Tablet.
- Desktop.

Avoid horizontal scrolling except where unavoidable for large financial tables.

---

## 13. Shared Hosting Rules

The application must remain deployable without long-running processes.

Production must not depend on:

```bash
npm run dev
php artisan serve
```

Assets must be compiled using:

```bash
npm run build
```

Shared hosting deployment must rely on standard PHP request execution.

---

## 14. Dependency Rules

Before adding a package:

1. Check whether Laravel already provides the capability.
2. Check whether a lightweight native implementation is sufficient.
3. Confirm shared-hosting compatibility.
4. Avoid abandoned packages.
5. Avoid packages requiring daemon processes.

Do not add dependencies only to solve trivial UI or utility problems.

---

## 15. AI Coding Rules

Before editing:

1. Inspect related existing files.
2. Read applicable documentation.
3. Understand the current implementation.
4. Identify the smallest safe change.
5. Preserve unrelated working features.

Do not:

- Rewrite entire files for minor edits.
- Recreate modules that already exist.
- Change database schema casually.
- Change route names without reason.
- Change public interfaces unnecessarily.
- Remove code simply because it appears unused without verifying references.
- Start the next sprint automatically.

---

## 16. Sprint Discipline

Each sprint must have a clear scope.

AI must stay inside the current sprint.

At sprint completion, report:

- Files created.
- Files modified.
- Migrations created.
- Routes added/changed.
- Database changes.
- Important implementation decisions.
- Known limitations.
- Manual steps required.

Do not proceed to the next sprint until instructed.

---

## 17. Testing Rules

If tests exist:

- Keep them passing.
- Update tests when behavior intentionally changes.

At minimum, manually verify:

- Authentication.
- Ownership protection.
- Transaction creation.
- Transaction update.
- Transfer integrity.
- Account balance reconciliation.
- Validation errors.
- Mobile layout.

---

## 18. Documentation Rules

Update documentation whenever changes affect:

- Database schema.
- Architecture.
- Deployment.
- Security.
- Design system.
- Product behavior.

Important structural changes must also be recorded in `CHANGELOG.md`.

---

## 19. Cleanup Rules

Before declaring a sprint complete:

- Remove debug dumps.
- Remove console debugging that is no longer needed.
- Remove temporary routes.
- Remove unused temporary files.
- Remove test/demo data from production code.
- Remove commented-out obsolete implementations where safe.
- Ensure no secrets are present.

---

## 20. Final Rule

When uncertain, prefer:

1. Data integrity.
2. Security.
3. Existing documented architecture.
4. Shared-hosting compatibility.
5. Minimal safe changes.

Do not guess structural decisions when the documentation already defines them.
