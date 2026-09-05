# SPRINT 11 — REPORTS & FINANCIAL ANALYTICS

## Objective

Implement reliable financial reports derived from persisted ledger data with filters for date, account, category, and transaction type.

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

Implement reports:

- Daily.
- Weekly.
- Monthly.
- Yearly.
- Custom date range.
- Account filter.
- Category filter.
- Type filter.
- Income total.
- Expense total.
- Net cash flow.
- Category breakdown.
- Account breakdown.

Do not implement exports yet unless explicitly included after core report correctness is verified.

---

# REPORTING RULES

Transfers must not be counted as income or expense.

Transfer fees may be represented as costs only according to existing financial logic.

Deleted/soft-deleted records must follow documented reporting policy.

---

# PERFORMANCE

Use efficient aggregate queries.

Paginate detailed ledgers.

Avoid loading all transaction history into PHP memory when SQL aggregation is appropriate.

---

# UI

Implement:

```text
/reports
```

with clear filters and summary.

Charts may use Chart.js.

No fake series.

---

# VERIFY

Use known manually created records and reconcile:

```text
Income
Expense
Net
Category totals
Account totals
```

Date boundaries must be correct for timezone.

---

# COMPLETION REPORT

Include report formulas and known accounting limitations.
