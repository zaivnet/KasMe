# SPRINT 09 — DEBTS & RECEIVABLES

## Objective

Implement debt and receivable tracking with safe payment history and remaining-balance reconciliation.

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

# SCOPE

Implement:

- Debt model.
- Debt payment model.
- Migrations.
- Debt / receivable CRUD.
- Payment history.
- Remaining amount.
- Due date.
- Status.
- Account selection for payment.
- Ownership protection.

---

# DATABASE

Use:

```text
debts
debt_payments
```

from `SCHEMA.md`.

Rules:

```text
original_amount > 0
remaining_amount >= 0
remaining_amount <= original_amount
```

---

# PAYMENT LOGIC

Debt payment operations must be atomic when they affect:

- Debt payment history.
- Remaining amount.
- Account financial effect.

Do not double-count account impact.

If integration with transaction ledger is implemented, define one canonical path and avoid duplicate expense/income effects.

---

# STATUS

Derived/managed states:

```text
active
paid
overdue
```

When remaining amount reaches zero, status should become paid.

---

# VERIFY

- Debt payment reduces remaining balance.
- Receivable payment reduces remaining balance.
- Overpayment rejected unless explicitly allowed.
- Payment reversal/edit remains consistent.
- Cross-user records rejected.
- Account effect reconciles.
- Build passes.

---

# COMPLETION REPORT

Explain exactly how debt payments affect account balances.
