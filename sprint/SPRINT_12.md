# SPRINT 12 — SETTINGS & USER PREFERENCES

## Objective

Implement application preferences such as currency, date format, timezone, and theme without altering financial records.

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

- Settings model.
- Migration.
- One settings record per user.
- Currency.
- Date format.
- Timezone.
- Theme.
- Settings UI.
- Application-wide preference usage.

---

# DATABASE

Follow `settings` schema from `SCHEMA.md`.

Unique:

```text
user_id
```

Defaults:

```text
currency: IDR
timezone: Asia/Jakarta
theme: system
```

---

# RULES

Changing display currency does NOT automatically convert historical monetary values.

Until exchange-rate support explicitly exists, currency setting is a display/default-entry preference only.

Do not implement hidden FX conversion.

---

# THEME

Support:

```text
light
dark
system
```

Use existing design system.

---

# VERIFY

- Settings created lazily or safely.
- User only edits own settings.
- Theme persists.
- Date format applied consistently.
- Timezone affects display/date calculations safely.
- No financial amount mutation.
- Build passes.

---

# COMPLETION REPORT

Document settings behavior and limitations.
