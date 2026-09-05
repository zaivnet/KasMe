# SPRINT 17.1 — FIX PROFILE MENU / USER DROPDOWN

## Objective

Refine and fix the user profile menu/dropdown in the top-right corner so it looks compact, modern, proportional, and consistent on both desktop and mobile.

This is a UI-only patch.

Do NOT modify authentication logic, logout logic, routes, database schema, or financial logic.

---

# CURRENT PROBLEM

The current user dropdown has several visual issues:

- Dropdown width is too large.
- Icons are excessively large.
- Spacing between menu items is too wide.
- The menu looks like a large side panel instead of a compact account dropdown.
- Profile / Settings / Logout rows are not visually balanced.
- Desktop appearance is awkward.
- Mobile appearance must not reuse the same oversized desktop dropdown.
- Logout icon is visually dominant and distracting.
- Menu alignment relative to the user trigger is not polished.

---

# REQUIRED DESKTOP DESIGN

Use a compact account dropdown.

Recommended size:

```text
width: 220–260px
```

Suggested structure:

```text
┌──────────────────────────┐
│ AZ  Ade Zaiv             │
│     email@example.com    │
├──────────────────────────┤
│ 👤  Profil               │
│ ⚙  Pengaturan           │
├──────────────────────────┤
│ ↪  Keluar                │
└──────────────────────────┘
```

Use the actual authenticated user name/email.

Do not show dummy email.

---

# USER TRIGGER

The topbar user trigger should be compact.

Desktop example:

```text
[ AZ ] Ade Zaiv  ˅
```

Requirements:

- Small avatar, approximately 32–36px.
- User name visible.
- Small chevron.
- Hover state.
- Focus state.
- Cursor pointer.
- Do not use a giant avatar.
- Keep alignment vertically centered.

If no profile photo exists, use initials.

Example:

```text
Ade Zaiv -> AZ
```

---

# DROPDOWN POSITION

Desktop dropdown should:

- Align to the right edge of user trigger.
- Open directly below the trigger.
- Use `absolute right-0 mt-2`.
- Have sufficient z-index.
- Never overflow viewport.
- Not push page layout.
- Not create a huge blank panel.

Recommended:

```text
z-50
w-60
rounded-xl
border
shadow-lg
```

Use subtle shadow only.

---

# MENU ITEM DESIGN

Each item should be a compact row.

Recommended height:

```text
40–44px
```

Structure:

```text
icon  label
```

Icon size:

```text
18–20px
```

NOT:

```text
60px
80px
100px
```

Use consistent icon size for:

- Profil
- Pengaturan
- Keluar

Recommended icon classes:

```text
w-5 h-5
```

---

# SPACING

Recommended:

```text
px-3 or px-4
py-2.5
gap-3
```

Avoid:

```text
large padding
huge vertical gaps
oversized icon containers
```

---

# PROFILE HEADER INSIDE DROPDOWN

At top of dropdown show a compact user summary.

Example:

```text
[AZ]  Ade Zaiv
      adezaiv@example.com
```

Recommended:

- Avatar: 36–40px.
- Name: font-medium.
- Email: text-xs/text-sm muted.
- Truncate long email.
- Padding around 12–16px.

If email is not intended to be displayed, omit it rather than showing placeholder data.

---

# SEPARATORS

Use subtle separators.

Example:

```text
user info
------------
profile
settings
------------
logout
```

Do not overuse borders.

---

# LOGOUT DESIGN

Logout must be visually distinct but not oversized.

Use:

```text
small red icon
small red text
subtle red hover background
```

Example Tailwind direction:

```text
text-red-600
hover:bg-red-50
dark:hover:bg-red-950/30
```

Keep existing secure POST logout behavior.

Do NOT change logout into GET.

---

# ICONS

Use the same icon library already used by the project.

Recommended icons:

```text
UserRound
Settings
LogOut
ChevronDown
```

Do not mix multiple icon families.

All dropdown icons:

```text
w-5 h-5
```

---

# INTERACTION

Use Alpine.js if already used.

Required behavior:

- Click user trigger -> toggle dropdown.
- Click outside -> close dropdown.
- Escape -> close dropdown if practical.
- Clicking menu link -> normal navigation.
- Logout form remains functional.
- Dropdown closes when appropriate.

Suggested Alpine pattern:

```text
x-data="{ open: false }"
x-show="open"
@click.outside="open = false"
@keydown.escape.window="open = false"
```

Use transition around:

```text
150–200ms
```

Avoid excessive animation.

---

# DESKTOP RESPONSIVE RULE

Desktop / tablet:

Use dropdown menu.

Do not use a large account panel.

Recommended breakpoint:

```text
md and above
```

---

# MOBILE BEHAVIOR

On mobile, do NOT force the same desktop dropdown if it looks cramped.

Use one of these existing mobile-native patterns:

Preferred:

```text
compact bottom sheet
```

or:

```text
small anchored menu below avatar
```

If the application already has a mobile "Lainnya" bottom sheet, integrate profile/settings/logout there rather than duplicating an oversized profile dropdown.

Recommended mobile approach:

When avatar is clicked:

```text
Bottom sheet
```

Structure:

```text
┌──────────────────────────────┐
│ ─────                        │
│ [AZ] Ade Zaiv                │
│      email                   │
│                              │
│ 👤 Profil                    │
│ ⚙ Pengaturan                │
│                              │
│ ↪ Keluar                     │
└──────────────────────────────┘
```

Bottom sheet should:

- Use rounded top corners.
- Have backdrop.
- Respect safe-area inset.
- Be easy to dismiss.
- Have compact rows.
- Not cover entire screen unnecessarily.

If existing "Lainnya" already includes profile/settings/logout, avatar click may simply open the same sheet or a dedicated compact account sheet.

Do not create duplicate navigation logic unnecessarily.

---

# MOBILE SIZING

Avatar:

```text
32–36px
```

Menu icon:

```text
20px
```

Menu row:

```text
48–52px
```

Sheet width:

```text
100%
```

Max height:

```text
content-based
```

Do not create full-screen modal unless necessary.

---

# DARK MODE

Ensure dropdown works in dark mode.

Check:

- background
- border
- text
- hover
- separators
- logout state
- avatar
- backdrop

No low-contrast gray-on-gray.

---

# ACCESSIBILITY

Required:

- User trigger has accessible label.
- `aria-expanded` where appropriate.
- Keyboard focus visible.
- Logout button is accessible.
- Icon-only trigger has tooltip/aria-label if user name is hidden on mobile.

---

# DO NOT CHANGE

Do NOT modify:

```text
login
logout backend logic
session handling
auth middleware
profile backend
settings backend
financial calculations
routes
database schema
```

This patch is presentation and interaction only.

---

# REQUIRED VERIFICATION

## Desktop

Test:

```text
1440px
1280px
1024px
```

Verify:

- Dropdown aligned correctly.
- Width compact.
- Icons normal size.
- No clipping.
- No layout shift.
- Click outside closes.
- Logout works.
- Profile works.
- Settings works.

## Mobile

Test:

```text
430px
390px
360px
```

Verify:

- Account menu is compact.
- No overflow.
- No giant icons.
- No viewport clipping.
- Easy to dismiss.
- Safe-area respected.
- Logout works.
- Profile/settings accessible.

---

# VISUAL TARGET

The result should feel like a modern banking/finance application account menu.

Compact.

Clean.

Professional.

Not like a large settings panel.

---

# FINAL CHECK

Before completion:

- Remove old oversized dropdown styles.
- Remove duplicated profile menu markup where safe.
- Keep only one desktop implementation.
- Keep only one appropriate mobile implementation.
- Verify no old giant icon classes remain.
- Run:

```bash
npm run build
```

---

# COMPLETION REPORT

Respond with:

```md
# SPRINT 17.1 COMPLETION REPORT

## Status
COMPLETE / PARTIAL / BLOCKED

## Desktop Dropdown
- Trigger:
- Width:
- Alignment:
- Icon sizing:
- Profile header:
- Logout style:

## Mobile Account Menu
- Pattern used:
- Width:
- Safe area:
- Interaction:

## Responsive Test
- 1440px:
- 1280px:
- 1024px:
- 430px:
- 390px:
- 360px:

## Interaction
- Click toggle:
- Click outside:
- Escape:
- Profile:
- Settings:
- Logout:

## Dark Mode
PASS / FAIL

## Build
npm run build: PASS / FAIL

## Backend Changes
NONE / explain if unavoidable

## Ready
YES / NO
```
