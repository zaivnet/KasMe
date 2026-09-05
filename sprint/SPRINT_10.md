# SPRINT 10 — SAVING GOALS

## Objective

Implement saving goals with contribution and withdrawal history while preserving account-balance integrity.

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

- Saving goal model.
- Saving goal transaction model.
- Migrations.
- Goal CRUD.
- Target amount.
- Target date.
- Status.
- Contributions.
- Withdrawals.
- Progress percentage.
- Linked account.
- Ownership security.

---

# DATABASE

Use:

```text
saving_goals
saving_goal_transactions
```

from `SCHEMA.md`.

Transaction types:

```text
contribution
withdrawal
```

---

# FINANCIAL INTEGRITY

A saving goal must not magically create money.

If contribution means moving money from an account into a logical goal, document whether:

1. It is tracking-only, or
2. It changes available account balance.

Use one consistent model.

Do not double-count the same movement.

All multi-record operations must be atomic.

---

# STATUS

Allowed:

```text
active
completed
cancelled
```

Goal may become completed when progress reaches target amount.

---

# VERIFY

- Contribution increases goal progress.
- Withdrawal reduces progress.
- Cannot withdraw beyond saved amount unless explicitly allowed.
- Account behavior reconciles.
- Cross-user access blocked.
- Build passes.

---

# COMPLETION REPORT

Explain the chosen saving-goal/account financial model.
