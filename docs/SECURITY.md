# SECURITY REQUIREMENTS

## 1. Security Objective

The application stores sensitive personal financial data.

Security requirements must therefore prioritize:

- Confidentiality.
- Integrity.
- Authentication.
- Authorization.
- Safe data handling.
- Secure deployment.

---

## 2. Authentication

Use Laravel-supported authentication mechanisms.

Requirements:

- Passwords must be hashed.
- Never store plaintext passwords.
- Sessions must be protected.
- Logout must invalidate session appropriately.
- Password reset tokens must follow framework best practices.

---

## 3. Authorization

Authentication alone is insufficient.

Every user-owned record must enforce ownership.

Protected resources include:

- Accounts.
- Categories.
- Transactions.
- Transfers.
- Budgets.
- Bills.
- Debts.
- Saving goals.
- Settings.
- Attachments.

Users must never access another user's records.

---

## 4. Query Security

Do not query user-owned models solely by public ID.

Unsafe:

```php
Transaction::findOrFail($id);
```

Prefer scoped or policy-protected access:

```php
auth()->user()->transactions()->findOrFail($id);
```

or equivalent authorization policy.

---

## 5. CSRF Protection

All state-changing web forms must use Laravel CSRF protection.

Never disable CSRF globally to solve form issues.

---

## 6. Validation

All write requests require server-side validation.

Never trust:

- Hidden inputs.
- JavaScript-calculated totals.
- Client-provided ownership IDs.
- Client-provided balances.

Validate:

- Amount.
- Date.
- Account ownership.
- Category ownership.
- Transfer ownership.
- Status.
- File type.
- File size.

---

## 7. Financial Operation Security

Critical financial operations must be atomic.

Use database transactions for:

- Transfers.
- Debt payments where multiple records are affected.
- Saving goal contributions if financial records are linked.
- Any operation modifying multiple dependent financial records.

---

## 8. Mass Assignment

Models must define safe mass-assignment behavior.

Use:

- `$fillable`, or
- appropriately controlled guarded strategy.

Never mass-assign uncontrolled request payloads.

Unsafe:

```php
Model::create($request->all());
```

Prefer validated input:

```php
Model::create($request->validated());
```

with additional ownership fields assigned server-side.

---

## 9. XSS Protection

Blade escaped output should be used by default.

Avoid `{!! !!}` unless content has been explicitly sanitized and trusted.

User notes and transaction descriptions should render escaped.

---

## 10. SQL Injection

Use:

- Eloquent.
- Query Builder.
- Parameter binding.

Never concatenate raw user input into SQL.

---

## 11. File Upload Security

Transaction attachments must be validated.

Validate:

- MIME type.
- Allowed extensions.
- Maximum file size.

Do not trust original filename.

Generate safe storage filenames.

Avoid storing executable uploads inside public directories.

If images/documents are served publicly, access design must prevent cross-user exposure.

---

## 12. Secrets

Never expose or commit:

- `.env`.
- APP_KEY.
- DB_PASSWORD.
- API tokens.
- SMTP password.
- Session secrets.

`.env` must remain outside public web exposure.

---

## 13. Production Configuration

Production must use:

```text
APP_ENV=production
APP_DEBUG=false
```

Never enable debug mode in production.

---

## 14. HTTPS

Production should use HTTPS.

Session cookies should use secure settings where supported.

Sensitive login and financial traffic must not be intentionally served over plaintext HTTP.

---

## 15. Session Security

Use Laravel session best practices.

Consider:

- Secure cookies on HTTPS.
- HTTP-only cookies.
- SameSite configuration.
- Session regeneration after login.

---

## 16. Rate Limiting

Apply rate limits to sensitive endpoints where appropriate.

Examples:

- Login.
- Password reset.
- Authentication-sensitive actions.

---

## 17. Error Handling

Do not expose:

- SQL queries.
- Stack traces.
- File paths.
- Environment variables.
- Internal secrets.

User-facing errors should be safe and understandable.

---

## 18. Logging Security

Never log:

- Passwords.
- Raw authentication tokens.
- Secret keys.
- Database credentials.

Be careful with request logging on financial forms.

---

## 19. Dependency Security

Avoid:

- Abandoned packages.
- Unmaintained authentication code.
- Unnecessary third-party libraries.

Periodically review Composer and npm dependencies.

---

## 20. Shared Hosting Security

Important shared-hosting protections:

- Public web root should point to Laravel `public/` when possible.
- `.env` must not be publicly accessible.
- Storage and bootstrap cache permissions must be correct.
- Directory listing should be disabled.
- PHP version must be supported.
- Debug mode must be disabled.
- HTTPS should be enabled.

If hosting forces Laravel outside a normal document-root layout, deployment must document the safe adaptation explicitly.

---

## 21. Backup & Restore Security

Database and attachment backups contain full sensitive financial records.

Strict security standards enforced:

- **Storage Location**: All backup archives are stored exclusively under `storage/app/private/backups/`. They are never stored or symlinked inside `public/` or `public/storage/`.
- **Authenticated Access**: Downloading, creating, and restoring backups requires authenticated session authorization scoped to the user.
- **Secret Exclusion**: Backups strictly exclude `.env`, `APP_KEY`, database credentials, `storage/logs/`, `storage/framework/`, sessions, and compiled views.
- **Zip Slip & Path Traversal Prevention**: Archive extraction validates every entry against `..`, leading slashes, Windows drive letters (`C:`), and sensitive target filenames before extraction.
- **Integrity Checksums**: Manifest files include SHA-256 checksums of payload files (`database/kasme.sql` and attachments). Mismatched or tampered archives are rejected prior to restoration.
- **Mandatory Pre-Restore Backup**: Every restore operation automatically creates an emergency `pre_restore` backup before modifying any data. If pre-restore backup creation fails, restore aborts immediately.
- **Safe Rollback**: If database execution fails mid-restore, the system rolls back to the pre-restore backup state.

---

## 22. Security Incident Principle

If data integrity is uncertain:

- Fail the operation.
- Roll back.
- Log appropriately.
- Do not silently continue.

Financial consistency is more important than completing a request partially.
