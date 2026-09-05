# SPRINT 02 — FINANCIAL ACCOUNTS

## Objective

Implement user-owned financial accounts such as cash, bank, e-wallet, savings, credit card, and other accounts.

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

- Account model.
- Account migration according to `SCHEMA.md`.
- Account relationships to User.
- Account CRUD.
- Opening balance.
- Account type.
- Currency.
- Optional icon/color.
- Active/inactive status.
- Soft delete/archive behavior.
- Account list.
- Account detail.
- Real calculated balance.
- Ownership authorization.

Do not implement transaction CRUD yet.

---

# DATABASE

Create only the `accounts` schema defined in `SCHEMA.md`.

Required fields include:

```text
id
user_id
name
type
opening_balance
currency
icon
color
is_active
created_at
updated_at
deleted_at
```

Use:

```text
DECIMAL(18,2)
```

for opening balance.

Do not introduce a manually editable `current_balance` column unless documentation is explicitly changed first.

---

# ACCOUNT BALANCE

At this sprint, before transaction modules exist, calculated balance may equal:

```text
opening_balance
```

Structure the code so future transactions/transfers can be included without rewriting the account module.

Do not accept authoritative balance values from frontend after account creation.

---

# UI

Implement:

```text
/accounts
/accounts/create
/accounts/{account}
/accounts/{account}/edit
```

Authenticated sidebar may now include:

```text
Home
Accounts
Profile
```

Account list should show only real records.

Empty state must be meaningful and contain an Add Account action.

No sample accounts.

---

# VALIDATION

Validate:

- name
- type
- opening_balance
- currency
- icon/color if accepted
- ownership

Allowed types must follow `SCHEMA.md`.

---

# DELETE / ARCHIVE RULE

Never silently destroy financial history.

At this sprint:

- Prefer soft-delete/archive.
- If an account has future financial dependencies, deletion rules must be safe.
- Do not add cascading hard deletes.

---

# TEST / VERIFY

Verify:

- User can create account.
- User can edit own account.
- User cannot access another user's account.
- Opening balance persists correctly.
- Account list is user-scoped.
- Soft delete/archive works.
- No dummy accounts exist.
- `npm run build` succeeds.

---

# CHANGELOG

Update `CHANGELOG.md` with actual Account module changes.

---

# COMPLETION REPORT

Report:

- Files created/modified/deleted.
- Migration created.
- Routes.
- Validation.
- Authorization.
- Balance approach.
- Build result.
- Known limitations.
- Ready for Sprint 03: YES/NO.
