# SPRINT 08 — BILLS & RECURRING OBLIGATIONS

## Objective

Implement bill tracking, due dates, recurrence metadata, and payment status without introducing unnecessary background services.

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

- Bill model.
- Migration.
- Bill CRUD.
- Amount.
- Due date.
- Optional category.
- Recurrence.
- Status.
- Notes.
- Upcoming bills.
- Overdue presentation.
- Optional dashboard widget.

Allowed recurrence:

```text
none
weekly
monthly
yearly
```

Statuses:

```text
unpaid
paid
overdue
```

---

# IMPORTANT

Do not create a complex recurring-job daemon.

For shared hosting, recurrence may initially be represented as bill metadata and handled synchronously/manual generation unless documentation explicitly defines automation.

Do not require Supervisor/Redis/WebSockets.

---

# PAYMENT BEHAVIOR

Do not silently create expense transactions when a bill is marked paid unless the workflow explicitly asks the user/account and the implementation is documented.

If payment-to-transaction linkage is introduced, it must be atomic and clearly designed.

---

# VERIFY

- Create/edit bill.
- Due dates display correctly.
- Overdue state derived safely.
- Recurrence values validated.
- Cross-user access blocked.
- No fake bills.
- Build passes.

---

# COMPLETION REPORT

Document whether bill payment currently creates a transaction or remains status-only.
