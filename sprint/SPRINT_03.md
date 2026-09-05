# SPRINT 03 — CATEGORIES

## Objective

Implement user-owned income and expense categories with optional parent-child categorization.

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

- Category model.
- Category migration per `SCHEMA.md`.
- User relationship.
- Parent-child relationship.
- Income/expense type.
- Icon/color.
- Active/inactive status.
- Category CRUD.
- Ownership authorization.
- Category filters.

Do not implement transactions yet.

---

# DATABASE

Create only documented `categories` structure.

Required:

```text
id
user_id
parent_id
name
type
icon
color
is_active
created_at
updated_at
```

Allowed type:

```text
income
expense
```

Do not invent default categories through seeders.

---

# RULES

- Parent category must belong to same user.
- Parent and child should use compatible category type.
- Prevent circular parent relationships.
- Disabled categories remain available for historical references later.
- Do not hard-delete categories that become referenced by financial data.

---

# UI

Routes/pages:

```text
/categories
/categories/create
/categories/{category}/edit
```

Support filters:

```text
All
Income
Expense
Active
Inactive
```

No dummy categories.

---

# VERIFY

- Create income category.
- Create expense category.
- Edit category.
- Parent-child relationship works.
- Cross-user access blocked.
- Circular parent blocked.
- No dummy data.
- Production build succeeds.

---

# COMPLETION REPORT

Report files, migration, routes, authorization, category hierarchy decisions, validation, build status, and readiness for Sprint 04.
