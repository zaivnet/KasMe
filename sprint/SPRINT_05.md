# SPRINT 05 — TRANSFERS BETWEEN ACCOUNTS

## Objective

Implement atomic transfers between user-owned financial accounts without creating or losing money.

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

- Transfer model.
- Transfer migration.
- Transfer creation.
- Transfer edit if safe.
- Transfer soft delete/reversal behavior.
- Source account.
- Destination account.
- Amount.
- Fee.
- Date.
- Description.
- Transfer history.
- Balance integration.
- Ownership authorization.

---

# DATABASE

Use `transfers` schema from `SCHEMA.md`.

Rules:

```text
from_account_id != to_account_id
amount > 0
fee >= 0
```

---

# FINANCIAL EFFECT

Source:

```text
-(amount + fee)
```

Destination:

```text
+amount
```

Updated account balance formula:

```text
opening_balance
+ income
- expense
+ positive_adjustments
- negative_adjustments
+ incoming_transfers
- outgoing_transfers
- transfer_fees
```

---

# ATOMICITY

Create/update/delete transfer operations that affect multiple financial records must use:

```php
DB::transaction(...)
```

Do not permit partial success.

---

# IMPORTANT

Do not represent one transfer as unrelated manual income and expense records unless architecture documentation is intentionally changed.

The `transfers` table remains the canonical transfer record for this project.

---

# UI

Implement:

```text
/transfers
/transfers/create
/transfers/{transfer}
```

Optionally edit only if implementation safely recalculates effects.

---

# VERIFY

Example:

Account A opening:

```text
1,000,000
```

Account B opening:

```text
200,000
```

Transfer:

```text
amount = 300,000
fee = 5,000
```

Expected:

```text
Account A = 695,000
Account B = 500,000
```

Verify:

- Same account transfer rejected.
- Cross-user accounts rejected.
- Edit/reversal remains balanced.
- Failed transaction rolls back.
- No duplicate financial effect.
- Build passes.

---

# COMPLETION REPORT

Include reconciliation example and readiness for Sprint 06.
