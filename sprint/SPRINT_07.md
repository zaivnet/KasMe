# SPRINT 07 — MONTHLY BUDGETS

## Objective

Implement monthly expense budgets per category and derive utilization from actual expense transactions.

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

- Budget model.
- Migration.
- Budget CRUD.
- Monthly/year filters.
- Expense-category-only budgets.
- Used amount.
- Remaining amount.
- Percentage utilization.
- Over-budget status.
- Dashboard integration if appropriate.

---

# DATABASE

Follow `SCHEMA.md`.

Unique constraint:

```text
user_id + category_id + month + year
```

---

# CALCULATION

Budget usage:

```text
sum(expense transactions)
```

for the same:

- user
- category
- month
- year

Do not store an authoritative `used_amount` unless documentation is changed for caching.

---

# UI

Pages:

```text
/budgets
/budgets/create
/budgets/{budget}/edit
```

Show:

- Budget amount.
- Used.
- Remaining.
- Progress.
- Over-budget.

---

# VERIFY

- Duplicate category/month/year budget blocked.
- Income category rejected.
- Usage matches transactions.
- Edited/deleted transaction changes utilization.
- Cross-user access blocked.
- Empty state works.
- Build passes.

---

# COMPLETION REPORT

Report calculation logic and readiness for Sprint 08.
