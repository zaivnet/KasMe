# SPRINT 01 — AUTHENTICATION & USER FOUNDATION

## Objective

Implement a secure authentication and user foundation for the Personal Finance Manager application.

This sprint must establish:

- User authentication.
- Guest/authenticated route separation.
- Login.
- Logout.
- Password reset flow.
- Basic profile management.
- User ownership foundation.
- Secure application layout for authenticated users.

Do not implement financial business modules yet.

---

# MANDATORY FIRST STEP

Before creating, modifying, deleting, or generating any code, you MUST read these project documents completely:

1. `PRD.md`
2. `ARCHITECTURE.md`
3. `RULES.md`
4. `SCHEMA.md`
5. `DESIGN_SYSTEM.md`
6. `SECURITY.md`
7. `DEPLOYMENT.md`
8. `CHANGELOG.md`

Also inspect the result of Sprint 00 before making changes.

These documents remain the source of truth.

If this sprint conflicts with the project documentation:

- Do not silently override the documentation.
- Follow the safer documented rule.
- Report the conflict in the final sprint report.

---

# PROJECT STACK

Use only the approved stack:

```text
Backend      : Laravel
Frontend     : Blade
CSS          : Tailwind CSS
Interactivity: Alpine.js
Database     : MySQL / MariaDB
Production   : Shared Hosting / cPanel
```

Do not introduce:

```text
React
Vue
Next.js
Nuxt
Inertia
Livewire
Docker
Redis
MongoDB
PM2
Supervisor
WebSocket Server
Node.js runtime in production
```

unless explicitly approved in project documentation.

---

# IMPORTANT AI RULES

Do not create speculative features.

Do not create dummy users.

Do not create demo accounts.

Do not create demo transactions.

Do not create dashboard financial data.

Do not create modules outside this sprint.

Do not change database schema outside the scope of authentication/user foundation.

Do not automatically continue to Sprint 02.

---

# SPRINT SCOPE

Sprint 01 includes only:

1. Inspect existing authentication state.
2. Implement or finalize authentication.
3. Implement login.
4. Implement logout.
5. Implement forgot password.
6. Implement reset password.
7. Implement authenticated route protection.
8. Implement guest route protection.
9. Implement basic profile management.
10. Implement password update.
11. Establish user ownership conventions.
12. Prepare authenticated application shell.
13. Validate authorization/security foundation.
14. Update documentation and changelog.
15. Produce completion report.

---

# STEP 1 — INSPECT EXISTING AUTHENTICATION

Inspect the project before installing anything.

Check for:

```text
routes
controllers
middleware
views
auth scaffolding
user model
password reset tables
session configuration
```

If authentication already exists and works:

```text
DO NOT install a second authentication system.
```

Improve the existing implementation only when necessary.

---

# STEP 2 — AUTHENTICATION APPROACH

Use standard Laravel authentication patterns compatible with:

```text
Blade
Tailwind CSS
Shared Hosting
```

Prefer lightweight authentication scaffolding.

Do not introduce an SPA authentication stack.

If an official Laravel Blade-based authentication starter is appropriate for the current Laravel version, it may be used.

After installation:

- Remove unused example/demo content.
- Keep only features required by this sprint.
- Ensure generated UI follows `DESIGN_SYSTEM.md`.

---

# STEP 3 — USER MODEL

Review:

```text
app/Models/User.php
```

The User model must support:

- Authentication.
- Password hashing.
- Password reset.
- Future relationships to financial entities.

Do not add financial relationships yet unless they are harmless placeholders explicitly required by architecture.

Do not add dummy profile fields that are not documented.

Core user fields must remain aligned with `SCHEMA.md`.

Expected core fields:

```text
id
name
email
email_verified_at
password
remember_token
created_at
updated_at
```

Do not modify this schema unnecessarily.

---

# STEP 4 — REGISTRATION POLICY

This application is primarily personal-use oriented.

Implement registration only if the existing product direction supports it.

Preferred initial behavior:

```text
Registration may exist but must be secure and clean.
```

If registration is enabled:

Required fields:

```text
name
email
password
password_confirmation
```

Rules:

- Email must be unique.
- Password must meet reasonable security requirements.
- Password must be hashed.
- Never store plaintext passwords.

Do not add financial fields during registration.

---

# STEP 5 — LOGIN

Implement a clean login page.

Fields:

```text
email
password
remember me
```

Actions:

```text
Login
Forgot Password
```

Requirements:

- Server-side validation.
- Secure session regeneration after login.
- Clear validation errors.
- Responsive layout.
- No fake credentials displayed.
- No demo account hints.
- No hardcoded username/password.

After successful login:

```text
redirect to authenticated application home
```

Use a safe placeholder application home if Dashboard financial data is not implemented yet.

---

# STEP 6 — LOGOUT

Implement secure logout.

Logout must:

1. Invalidate authenticated session appropriately.
2. Regenerate CSRF token where applicable.
3. Redirect to login or public landing page.

Logout must use a state-changing request pattern supported by Laravel security practices.

Do not implement logout as an unsafe GET action if the framework setup uses POST.

---

# STEP 7 — FORGOT PASSWORD

Implement forgot password flow.

Page:

```text
Forgot Password
```

Field:

```text
email
```

Requirements:

- Validate email.
- Use Laravel password reset facilities.
- Avoid revealing excessive account existence information where practical.
- Display a safe status message.

If mail transport is not configured:

- Keep the functionality code-ready.
- Document that email delivery depends on production SMTP configuration.
- Do not fake successful email delivery.

---

# STEP 8 — RESET PASSWORD

Implement reset password form.

Required fields:

```text
email
password
password_confirmation
token
```

Requirements:

- Validate token.
- Validate password confirmation.
- Hash password.
- Invalidate/reset token after use according to Laravel behavior.
- Redirect safely after completion.

---

# STEP 9 — EMAIL VERIFICATION

Email verification is optional unless already enabled by the chosen authentication implementation.

If enabled:

- Use Laravel-supported verification.
- Keep it fully functional.
- Do not require a custom background worker.

If not enabled:

- Do not add it merely for complexity.
- Mention its status in the completion report.

---

# STEP 10 — AUTHENTICATED ROUTE PROTECTION

All authenticated application routes must be protected using:

```text
auth middleware
```

Guest-only routes must use:

```text
guest middleware
```

Examples:

Guest:

```text
/login
/register
/forgot-password
/reset-password
```

Authenticated:

```text
/app
/profile
```

Do not create financial routes yet.

---

# STEP 11 — AUTHENTICATED APPLICATION HOME

Create a minimal authenticated home page.

Recommended route:

```text
/app
```

or another clean route consistent with the project.

This is NOT the financial dashboard yet.

Display only safe real user information such as:

```text
Welcome, {authenticated user name}
```

Possible content:

```text
Application shell
Profile link
Logout action
Short message that financial modules will be added later
```

Do not create:

```text
fake balances
fake income
fake expenses
fake charts
fake recent transactions
```

---

# STEP 12 — PROFILE PAGE

Implement basic profile management.

Allowed editable fields:

```text
name
email
```

Requirements:

- Server-side validation.
- Email uniqueness excluding current user.
- Ownership implicit through authenticated session.
- Clear success message.

If changing email affects verification state, handle it according to the chosen Laravel authentication implementation.

---

# STEP 13 — PASSWORD UPDATE

Implement a password update section.

Fields:

```text
current_password
password
password_confirmation
```

Requirements:

- Verify current password.
- Validate new password.
- Hash new password.
- Never display saved password.
- Never log password fields.

---

# STEP 14 — ACCOUNT DELETION

Do NOT implement user account deletion in this sprint unless it is already present from generated auth scaffolding.

Reason:

Future financial records require explicit deletion policy.

If generated scaffolding includes account deletion:

- Disable/remove it if removal is safe and within scope.
- Or clearly mark it unavailable until financial data lifecycle rules are implemented.

Never allow user deletion to cascade-destroy future financial data without a documented policy.

---

# STEP 15 — AUTHENTICATED LAYOUT

Prepare authenticated layout following `DESIGN_SYSTEM.md`.

Expected structure:

```text
resources/views/layouts/app.blade.php
```

Desktop foundation:

```text
Sidebar
Topbar
Main Content
```

Mobile foundation:

```text
Mobile header
Collapsible navigation
Main content
```

Navigation in Sprint 01 should include only implemented destinations.

Example:

```text
Home
Profile
Logout
```

Do not show menu items for unfinished modules.

Forbidden premature navigation:

```text
Accounts
Transactions
Transfers
Budgets
Bills
Debts
Reports
Saving Goals
```

until those modules actually exist.

---

# STEP 16 — GUEST LAYOUT

Prepare a clean guest layout for:

```text
Login
Register
Forgot Password
Reset Password
```

Requirements:

- Modern.
- Minimal.
- Responsive.
- Consistent with `DESIGN_SYSTEM.md`.
- No unnecessary marketing content.
- No dummy financial screenshots.

---

# STEP 17 — FLASH MESSAGES

Provide reusable feedback UI for:

```text
success
error
status
validation
```

Use Blade components if appropriate.

Messages must not expose internal exceptions.

---

# STEP 18 — USER OWNERSHIP FOUNDATION

Future financial models will be user-owned.

Establish the project convention now:

```text
Every user-owned model must be accessed through authenticated ownership checks.
```

Document the intended pattern.

Preferred examples:

```php
auth()->user()->accounts()
auth()->user()->transactions()
```

or equivalent policies/scoped queries.

Do not create actual financial models in Sprint 01.

---

# STEP 19 — AUTHORIZATION FOUNDATION

If appropriate, prepare standard Laravel policy conventions.

Do not generate empty policies for every future model.

Only establish the pattern/documentation required for future implementation.

Avoid unnecessary files.

---

# STEP 20 — RATE LIMITING

Verify that authentication endpoints use Laravel-supported throttling where available.

Particularly:

```text
login
password reset request
```

Do not implement custom Redis-based rate limiting.

Use shared-hosting-compatible framework defaults.

---

# STEP 21 — SESSION CONFIGURATION

Use shared-hosting-friendly sessions.

Preferred:

```text
file
```

or another documented Laravel-compatible driver that works in the current hosting setup.

Do not require Redis.

Ensure production configuration remains controlled through `.env`.

---

# STEP 22 — CSRF

Verify CSRF remains enabled for all state-changing web forms.

Forms must include Laravel CSRF tokens.

Never disable CSRF to solve an implementation problem.

---

# STEP 23 — PASSWORD SECURITY

Use Laravel password hashing.

Never use:

```text
md5
sha1
custom plaintext encryption
```

Do not manually invent password hashing logic.

---

# STEP 24 — VALIDATION

All authentication and profile write operations must validate server-side.

Minimum checks:

## Registration

```text
name
email
password
password_confirmation
```

## Login

```text
email
password
```

## Profile

```text
name
email
```

## Password Update

```text
current_password
password
password_confirmation
```

---

# STEP 25 — ROUTE REVIEW

Run:

```bash
php artisan route:list
```

Review for:

- Duplicate auth routes.
- Unprotected application routes.
- Unexpected generated feature routes.
- Unsafe logout route.
- Unused demo routes.

Remove or fix only within Sprint 01 scope.

---

# STEP 26 — DATABASE MIGRATIONS

Sprint 01 may use authentication-related framework tables only.

Allowed where required:

```text
users
password_reset_tokens
sessions
```

depending on Laravel version/configuration.

Do not create financial tables.

Do not run destructive migration commands.

Forbidden:

```bash
php artisan migrate:fresh
php artisan db:wipe
php artisan migrate:refresh
```

against an unknown or persistent database.

Use:

```bash
php artisan migrate
```

only when database configuration is confirmed safe.

---

# STEP 27 — NO DUMMY DATA

Absolute rule:

Do not create:

```text
demo user
sample login account
default password
factory-generated user
fake profile
fake balance
fake transaction
```

Do not run application seeders that insert dummy data.

---

# STEP 28 — TESTING

Perform available verification.

At minimum:

## Authentication

- Guest can open login.
- Authenticated user cannot access guest-only login unnecessarily.
- Valid login works.
- Invalid login fails safely.
- Logout works.
- Authenticated route redirects guest.
- Forgot password page works.
- Reset flow is code-ready.

## Profile

- Profile page requires authentication.
- User can update name.
- User can update email with validation.
- User can update password with current password verification.

## Security

- CSRF enabled.
- Password hashing confirmed.
- No plaintext password storage.
- No secrets exposed.
- No dummy users.

---

# STEP 29 — FRONTEND BUILD

Run:

```bash
npm run build
```

Ensure production asset build succeeds.

Do not leave the project dependent on:

```bash
npm run dev
```

for production.

---

# STEP 30 — CODE QUALITY CHECK

Before completion inspect for:

- Duplicate auth controllers.
- Duplicate auth routes.
- Unused generated views.
- Broken navigation.
- Debug code.
- Console logs.
- Hardcoded credentials.
- Dummy users.
- Demo text.
- Dead generated components.
- Unnecessary packages.

Remove only what is clearly safe and within scope.

---

# STEP 31 — UPDATE README

Update `README.md` only if authentication setup introduces required setup information.

Possible additions:

```text
Authentication setup
Mail requirement for password reset
Session configuration
```

Do not duplicate full security documentation.

---

# STEP 32 — UPDATE CHANGELOG

Update:

```text
CHANGELOG.md
```

Under:

```text
[Unreleased]
```

Record actual Sprint 01 work.

Example:

```md
### Added
- User login and logout.
- Password reset flow.
- Authenticated application shell.
- User profile management.
- Password update.
- Authentication route protection.

### Security
- Added authenticated/guest middleware separation.
- Added secure password handling and session protection.
```

Only list features actually implemented.

---

# DEFINITION OF DONE

Sprint 01 is complete only when:

- [ ] All project documentation has been read.
- [ ] Sprint 00 result has been inspected.
- [ ] Authentication system is implemented or verified.
- [ ] Login works.
- [ ] Logout works.
- [ ] Forgot password flow exists.
- [ ] Reset password flow exists.
- [ ] Authenticated routes are protected.
- [ ] Guest routes are protected.
- [ ] Profile page works.
- [ ] Password update works.
- [ ] No unsafe account deletion behavior exists.
- [ ] Authenticated layout exists.
- [ ] Guest layout exists.
- [ ] No financial module has been implemented.
- [ ] No dummy user exists.
- [ ] No dummy financial data exists.
- [ ] CSRF protection remains active.
- [ ] Passwords use Laravel hashing.
- [ ] Route review passes.
- [ ] Frontend production build succeeds.
- [ ] Shared-hosting compatibility is preserved.
- [ ] README is updated if needed.
- [ ] CHANGELOG.md is updated.
- [ ] Final completion report is produced.

---

# STRICTLY FORBIDDEN IN SPRINT 01

Do NOT implement:

```text
Account CRUD
Category CRUD
Transaction CRUD
Transfer CRUD
Budget CRUD
Bill CRUD
Debt CRUD
Saving Goal CRUD
Financial Dashboard
Financial Reports
Charts with financial data
PDF Export
Excel Export
Recurring Transactions
Bank API
Payment Gateway
Notifications
```

Do not create unfinished menu links for these modules.

---

# FINAL SPRINT REPORT FORMAT

When Sprint 01 is complete, respond using this exact structure:

```md
# SPRINT 01 COMPLETION REPORT

## Status
COMPLETE / PARTIAL / BLOCKED

## Documentation Read
- PRD.md
- ARCHITECTURE.md
- RULES.md
- SCHEMA.md
- DESIGN_SYSTEM.md
- SECURITY.md
- DEPLOYMENT.md
- CHANGELOG.md

## Sprint 00 State Reviewed
Describe relevant existing foundation.

## Authentication Approach
Describe the authentication implementation used.

## Files Created
- ...

## Files Modified
- ...

## Files Deleted
- ...

## Packages Added
### Composer
- ...

### NPM
- ...

## Database Changes
- ...

## Routes Added / Modified
- ...

## Authentication Features
- Login: PASS / FAIL
- Logout: PASS / FAIL
- Registration: ENABLED / DISABLED
- Forgot Password: PASS / FAIL
- Reset Password: PASS / FAIL
- Email Verification: ENABLED / DISABLED
- Profile Update: PASS / FAIL
- Password Update: PASS / FAIL

## Security Verification
- CSRF: PASS / FAIL
- Password Hashing: PASS / FAIL
- Auth Middleware: PASS / FAIL
- Guest Middleware: PASS / FAIL
- Route Protection: PASS / FAIL
- Dummy User Check: PASS / FAIL
- Hardcoded Credential Check: PASS / FAIL

## Frontend Verification
- Responsive auth pages: PASS / FAIL
- Authenticated layout: PASS / FAIL
- Production build: PASS / FAIL

## Commands Executed
- ...

## Important Decisions
- ...

## Known Limitations
- ...

## Manual Actions Required
- ...

## Documentation Updated
- README.md
- CHANGELOG.md

## Ready for Sprint 02
YES / NO
```

---

# FINAL INSTRUCTION

Do not proceed beyond Sprint 01.

Do not implement financial modules proactively.

Do not create dummy data.

Preserve the architecture established in Sprint 00.

Wait for explicit instruction before starting Sprint 02.
