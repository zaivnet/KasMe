# SPRINT 13 — ATTACHMENTS, DATA EXPORT & BACKUP SAFETY

## Objective

Harden transaction attachment handling and add safe user-controlled data export/backup capabilities suitable for shared hosting.

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

Implement only where compatible with existing project:

- Transaction attachment upload.
- Attachment replace/remove.
- Safe file validation.
- Ownership-protected attachment access.
- CSV export for transaction/report data.
- Optional JSON data export for personal backup.
- Backup guidance.

PDF/Excel libraries should not be added unless necessary.

Prefer CSV before heavy spreadsheet dependencies.

---

# ATTACHMENT SECURITY

Validate:

- MIME.
- Extension.
- Size.
- Ownership.

Never trust original filename.

Do not expose private files by predictable path if access control is required.

---

# EXPORT

CSV export should respect current filters where applicable.

Export only authenticated user's data.

Do not include:

- Password hash.
- Session data.
- Tokens.
- Secrets.

---

# BACKUP

Do not implement public database dump endpoints.

If JSON export is implemented, it should be a user-data export, not raw SQL credentials/database dump.

---

# VERIFY

- Cross-user attachment access blocked.
- Invalid file rejected.
- CSV contains only user's records.
- No secrets exported.
- Large export handled reasonably.
- Shared-hosting compatibility preserved.

---

# COMPLETION REPORT

Document export formats, file storage path/strategy, and security protections.
