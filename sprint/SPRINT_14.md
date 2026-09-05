# SPRINT 14 — SECURITY, AUTHORIZATION & FINANCIAL INTEGRITY AUDIT

## Objective

Audit the completed application for authorization gaps, financial inconsistencies, unsafe uploads, insecure configuration, and accidental cross-user access.

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

# AUDIT ONLY

Do not redesign the application.

Fix verified issues within scope.

---

# AUDIT AREAS

Review:

- Authentication.
- Authorization.
- Policies/scoped queries.
- CSRF.
- Validation.
- Mass assignment.
- XSS.
- SQL injection risks.
- File uploads.
- Secrets.
- Debug mode.
- Financial atomicity.
- Balance calculations.
- Transfer reconciliation.
- Debt payment reconciliation.
- Saving goal reconciliation.
- Soft deletes.
- Cross-user access.
- Route protection.

---

# OWNERSHIP ATTACK TESTS

Attempt controlled checks such as:

```text
User A accessing User B account ID
User A accessing User B transaction ID
User A accessing User B attachment
User A modifying User B transfer
```

All must fail.

---

# FINANCIAL RECONCILIATION

For each account compare application balance to formula:

```text
opening_balance
+ income
- expense
+ adjustments
+ incoming transfers
- outgoing transfers
- fees
+/- any documented debt/saving-goal effects
```

No unexplained discrepancy is acceptable.

---

# CLEANUP

Remove:

- Debug routes.
- `dd()`.
- `dump()`.
- temporary console logs.
- hardcoded credentials.
- fake data.
- obsolete development pages.

---

# COMPLETION REPORT

Include:

- Vulnerabilities found.
- Fixes applied.
- Items not applicable.
- Reconciliation result.
- Cross-user test result.
- Remaining risks.
- Ready for Sprint 15.
