# SPRINT 06 — REAL FINANCIAL DASHBOARD

## Objective

Build the main dashboard using only persisted financial data from accounts, transactions, transfers, and categories.

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

Implement dashboard metrics:

- Total balance.
- Income this month.
- Expenses this month.
- Net cash flow.
- Account summary.
- Recent transactions.
- Income vs expense chart.
- Expense by category chart.

Do not implement budgets or bills yet.

---

# DATA RULE

Every dashboard value must come from real database records.

Forbidden:

```text
hardcoded amounts
random values
sample chart series
demo transaction lists
```

Empty database must show zeros/empty states.

---

# DATE LOGIC

Use application/user timezone:

```text
Asia/Jakarta
```

unless user setting later overrides it.

Monthly metrics must use the current calendar month correctly.

---

# CHARTS

Use Chart.js.

Recommended:

- Income vs Expense: bar or line chart.
- Expense by Category: doughnut chart.

Charts must gracefully handle empty data.

---

# PERFORMANCE

Avoid N+1 queries.

Do not calculate each dashboard card with wasteful repeated queries when aggregations can be efficient.

---

# UI

Authenticated home may now become:

```text
/dashboard
```

or existing `/app` may be redirected to dashboard.

Sidebar:

```text
Dashboard
Accounts
Categories
Transactions
Transfers
Profile
```

Only implemented modules.

---

# VERIFY

- Empty state shows 0 / no data.
- Metrics reconcile with ledger.
- Current month filter correct.
- Transfer is not counted as income.
- Transfer is not counted as expense.
- Transfer fee is treated according to documented financial formula.
- Charts match backend aggregates.
- Mobile layout works.
- Build passes.

---

# COMPLETION REPORT

Include metric definitions and reconciliation notes.
