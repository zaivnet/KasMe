# SPRINT 04 — INCOME, EXPENSE & ADJUSTMENT TRANSACTIONS

## Objective

Implement the primary financial ledger for income, expenses, and explicit balance adjustments.

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

# CRITICAL FINANCIAL SPRINT

Financial integrity has priority over UI convenience.

---

# SCOPE

Implement:

- Transaction model.
- Migration per `SCHEMA.md`.
- Transaction CRUD.
- Income.
- Expense.
- Adjustment.
- Account and category relationships.
- Transaction date.
- Description.
- Soft delete.
- Account balance calculation using transaction data.
- Transaction filters.
- Pagination.
- Ownership authorization.

Do not implement transfers yet.

---

# DATABASE

Create `transactions` exactly according to `SCHEMA.md`.

Important:

```text
amount DECIMAL(18,2)
amount > 0
```

Types:

```text
income
expense
adjustment
```

Adjustment direction:

```text
increase
decrease
```

---

# BALANCE FORMULA

After this sprint:

```text
opening_balance
+ income
- expense
+ positive_adjustments
- negative_adjustments
```

Balance calculation must occur server-side.

Never send/accept an authoritative `balance` value from forms.

---

# BUSINESS LOGIC

Prefer dedicated action/service classes for:

```text
CreateTransaction
UpdateTransaction
DeleteTransaction
CalculateAccountBalance
```

Editing transaction amount/account/type must immediately produce the correct derived balance.

Soft-deleting a transaction must also be reflected in balance calculation.

---

# CATEGORY RULES

For income:

- Category should be income type.

For expense:

- Category should be expense type.

For adjustment:

- Category may be nullable according to schema/documented implementation.

All related account/category records must belong to authenticated user.

---

# UI

Routes/pages:

```text
/transactions
/transactions/create
/transactions/{transaction}/edit
/transactions/{transaction}
```

Filters:

- Date range.
- Account.
- Category.
- Type.

Do not show fake statistics.

---

# VERIFY

Test manually:

1. Opening balance 1,000,000.
2. Income 500,000.
3. Expense 200,000.
4. Increase adjustment 50,000.
5. Decrease adjustment 25,000.

Expected derived balance:

```text
1,325,000
```

Also verify:

- Editing income amount changes balance correctly.
- Moving transaction to another account changes both account totals correctly.
- Soft deletion removes financial effect.
- Cross-user access fails.
- Invalid category/type combination fails.
- No FLOAT/DOUBLE.
- Build passes.

---

# COMPLETION REPORT

Include balance reconciliation evidence and readiness for Sprint 05.
