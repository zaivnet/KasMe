# SPRINT 15 — PRODUCTION OPTIMIZATION & RELEASE HARDENING

## Objective

Prepare the application for production by optimizing queries, assets, error handling, caching compatibility, documentation, and release cleanliness.

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

Review and optimize:

- Database queries.
- N+1 problems.
- Index usage.
- Pagination.
- Blade rendering.
- Chart payload sizes.
- Asset build.
- Composer production dependencies.
- Logging.
- Production error handling.
- Cache compatibility.
- Storage.
- README.
- Deployment docs.

---

# DATABASE

Do not add indexes blindly.

Use actual query patterns.

Any schema/index change must update `SCHEMA.md`.

---

# FRONTEND

Run:

```bash
npm run build
```

Ensure no dependency on Vite dev server.

Remove unused frontend packages if confidently safe.

---

# LARAVEL PRODUCTION

Verify compatibility with:

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If route cache cannot work, identify and fix the reason if within architecture.

---

# PRODUCTION CONFIG

Ensure documentation uses:

```text
APP_ENV=production
APP_DEBUG=false
```

No secrets committed.

---

# FINAL CLEANUP

Search for:

```text
TODO
FIXME
dd(
dump(
console.log
localhost
127.0.0.1
example password
dummy
sample
demo
```

Do not remove legitimate references blindly; inspect first.

---

# VERIFY

- PHP application boots.
- Routes cache if applicable.
- Config cache works.
- View cache works.
- Production asset build works.
- Core flows still work.
- No dummy data.
- No dev-only runtime dependency.
- Shared-hosting requirements remain satisfied.

---

# COMPLETION REPORT

Provide release-readiness status and unresolved blockers.
