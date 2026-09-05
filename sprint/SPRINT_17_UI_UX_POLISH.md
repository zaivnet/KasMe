# SPRINT 17 — MODERN UI/UX POLISH & MOBILE APP-LIKE EXPERIENCE

## Objective

Modernize the existing Personal Finance Manager UI/UX while preserving all working business logic, financial calculations, routes, database schema, authorization rules, and data integrity.

This sprint is strictly for UI/UX refinement.

Primary goals:

1. Make the desktop UI modern, compact, professional, and premium.
2. Make the mobile experience feel like a native Android finance application when opened from a mobile browser.
3. Improve navigation, forms, cards, tables, empty states, charts, feedback, and responsiveness.
4. Preserve all financial functionality exactly as it currently works.
5. Do not change financial calculations unless fixing a confirmed UI-related bug.

---

# MANDATORY FIRST STEP

Before modifying anything, read completely:

1. `PRD.md`
2. `ARCHITECTURE.md`
3. `RULES.md`
4. `SCHEMA.md`
5. `DESIGN_SYSTEM.md`
6. `SECURITY.md`
7. `DEPLOYMENT.md`
8. `CHANGELOG.md`

Also inspect the completed implementation from Sprint 00–16.

Do not start redesign before understanding the current Blade layouts and reusable components.

---

# ABSOLUTE SAFETY RULE

This sprint must NOT change:

```text
financial calculation logic
account balance formulas
transaction behavior
transfer behavior
budget calculation
debt/receivable calculation
saving goal calculation
database schema
database relationships
authorization ownership rules
existing route behavior
validated backend workflows
```

If a UI improvement requires backend changes, keep them minimal and report them clearly.

Do not refactor stable financial services only for cosmetic reasons.

---

# APPROVED STACK

Continue using:

```text
Laravel
Blade
Tailwind CSS
Alpine.js
Chart.js
```

Do NOT convert the project to:

```text
React
Vue
Next.js
Nuxt
Inertia
Livewire
Flutter
React Native
```

The project remains a responsive web application.

---

# DESIGN DIRECTION

The UI should feel like a polished modern personal finance application.

Design characteristics:

- Clean.
- Minimal.
- Premium.
- Modern.
- Compact.
- Strong visual hierarchy.
- Rounded surfaces.
- Subtle borders.
- Soft shadows.
- Consistent iconography.
- Excellent mobile ergonomics.

Avoid:

- Excessive empty space.
- Old-style admin dashboard appearance.
- Tiny text.
- Huge blank sections.
- Heavy card borders.
- Random colors.
- Desktop-only layouts.
- Poorly spaced forms.

---

# UI LANGUAGE

Preserve the language currently used in the interface.

If the current interface is Indonesian, keep visible UI consistently Indonesian.

---

# ICON SYSTEM

Use one icon library consistently.

Preferred:

```text
Lucide Icons
```

If another icon system is already implemented consistently, reuse it instead of adding another library.

Suggested navigation mapping:

```text
Dasbor          -> LayoutDashboard
Laporan         -> ChartNoAxesCombined
Akun            -> WalletCards
Kategori        -> Tags
Transaksi       -> ArrowLeftRight
Transfer        -> Repeat2
Anggaran        -> PieChart
Tagihan         -> ReceiptText
Utang & Piutang -> HandCoins
Target Tabungan -> Goal
Pengaturan      -> Settings
Profil          -> UserRound
Keluar          -> LogOut
```

---

# GLOBAL APP SHELL — DESKTOP

Redesign the desktop application shell.

Target layout:

```text
+-----------------------------------------------------------+
| Sidebar       | Topbar                                    |
|               +-------------------------------------------+
| Navigation    | Main Content                              |
|               |                                           |
+-----------------------------------------------------------+
```

Sidebar target width:

```text
240–270px
```

Sidebar should contain:

- Brand/logo.
- Navigation groups.
- Active menu indicator.
- Bottom user/profile area.
- Collapse control.

Recommended grouping:

```text
UTAMA
- Dasbor
- Laporan

KEUANGAN
- Akun
- Kategori
- Transaksi
- Transfer

PERENCANAAN
- Anggaran
- Tagihan
- Utang & Piutang
- Target Tabungan

SISTEM
- Pengaturan
- Profil
```

Keep group labels subtle.

---

# COLLAPSIBLE DESKTOP SIDEBAR

Add sidebar collapse behavior using Alpine.js.

Expanded:

```text
icon + label
```

Collapsed:

```text
icon only
```

Requirements:

- Tooltip for collapsed icons.
- Active state remains visible.
- Use localStorage to remember state if practical.
- Main content width adapts smoothly.
- No unnecessary backend persistence.

---

# TOPBAR

Modernize topbar.

Desktop topbar may include:

- Sidebar toggle.
- Page title/context.
- Quick add transaction action.
- User avatar/name dropdown.

Replace plain text user/logout presentation with a compact user menu.

Example:

```text
Ade Zaiv
- Profil
- Pengaturan
- Keluar
```

Logout must preserve secure existing POST behavior.

---

# PAGE CONTAINER

Reduce excessive whitespace.

Use a consistent responsive container.

Recommended dashboard/report max width:

```text
1400–1500px
```

Recommended form max width:

```text
720–900px
```

Use responsive horizontal padding.

---

# PAGE HEADER COMPONENT

Create or refine a reusable page header.

Structure:

```text
Title
Description
Primary action
Optional secondary action
```

Example:

```text
Akun
Kelola rekening, dompet, dan sumber dana Anda.

[+ Tambah Akun]
```

---

# DASHBOARD REDESIGN

Do not change calculations.

## Summary Cards

Metrics:

```text
Total saldo
Pemasukan bulan ini
Pengeluaran bulan ini
Arus kas bersih
```

Improve with:

- Semantic icon.
- Compact label.
- Strong value.
- Contextual helper text.
- Consistent card height.
- Better spacing.
- Subtle shadow/border.

Desktop:

```text
4 columns
```

Tablet:

```text
2 columns
```

Mobile:

```text
2 compact columns
```

Avoid giant full-width cards on mobile.

---

# DASHBOARD ACCOUNT SUMMARY

Each account item should display:

```text
icon
account name
account type
current balance
status
```

Use account color as subtle accent.

---

# DASHBOARD RECENT TRANSACTIONS

Modernize rows.

Display:

```text
category/account icon
transaction label
account/category
date
amount
```

Income:

```text
positive visual state
```

Expense:

```text
negative visual state
```

Avoid heavy table borders.

---

# DASHBOARD CHARTS

Keep Chart.js.

Improve:

- Card padding.
- Legend placement.
- Axis labels.
- Tooltip formatting.
- Responsive height.
- Dark mode.
- Currency formatting.

If no data exists, show polished empty-state instead of blank chart canvas.

---

# ACCOUNT LIST REDESIGN

Desktop:

```text
responsive card grid
```

Mobile:

```text
stacked wallet-style cards
```

Each account card:

```text
icon
account name
type
balance
status
optional accent
quick actions
```

---

# ACCOUNT FORM UX

Existing fields:

```text
Nama akun
Jenis
Mata uang
Saldo awal
Nama ikon
Warna
Akun aktif
```

Improve them.

## Icon

Replace raw `Nama ikon` text field with a visual icon picker.

Requirements:

- Curated icon list.
- Store compatible icon identifier.
- Preserve old saved values.
- Do not break existing data.

## Color

Use:

```text
preset color swatches
+
custom color picker
```

Show selected color clearly.

## Amount

Provide friendly number formatting preview where safe.

Backend submitted value must remain validated numeric data.

---

# CATEGORY UX

Category form should use:

```text
name
type
parent
icon picker
color picker
status
```

Use clear visual distinction for:

```text
Pemasukan
Pengeluaran
```

Do not use color as the only indicator.

---

# TRANSACTION UX

Optimize for fast daily use.

Use a segmented control:

```text
Pemasukan | Pengeluaran | Penyesuaian
```

Mobile form hierarchy:

```text
Jenis transaksi
Nominal
Akun
Kategori
Tanggal
Catatan
Lampiran
Simpan
```

Nominal should be visually prominent.

Use:

```html
inputmode="decimal"
```

where appropriate.

---

# QUICK ADD TRANSACTION

Add a prominent quick-add action.

Desktop:

```text
+ Tambah transaksi
```

Mobile:

Use a Floating Action Button (FAB):

```text
+
```

or:

```text
+ Transaksi
```

FAB must not obscure content or bottom navigation.

Use existing transaction creation route.

---

# TRANSFER UX

Make movement visually clear:

```text
Dari akun
    ↓
Ke akun
```

Show:

- Source account balance.
- Destination account.
- Amount.
- Fee.
- Date.

Frontend preview is allowed, but backend remains authoritative.

---

# BUDGET UX

Each budget item should show:

```text
category
budget
used
remaining
progress
status
```

Semantic states:

```text
Normal
Mendekati batas
Melebihi anggaran
```

Use progress bars.

---

# BILL UX

Bill list should emphasize:

```text
nama tagihan
jumlah
jatuh tempo
status
recurrence
```

Status:

```text
Belum dibayar
Lunas
Terlambat
```

---

# DEBT & RECEIVABLE UX

Use tabs or segmented controls:

```text
Utang | Piutang
```

Each item:

```text
person/entity
remaining
original
due date
progress
status
```

---

# SAVING GOAL UX

Each goal:

```text
icon
goal name
current amount
target amount
percentage
target date
progress bar
```

Keep the presentation motivational but professional.

---

# REPORT UX

Desktop filter bar:

```text
Tanggal | Akun | Kategori | Jenis | Terapkan
```

Mobile:

Use a single:

```text
Filter
```

button opening a bottom-sheet-like filter panel with Alpine.js.

Do not add a heavy mobile UI library.

---

# SETTINGS UX

Group settings:

```text
Regional
- Currency
- Timezone
- Date format

Appearance
- Light
- Dark
- System

Account
- Profile
- Password
```

Avoid one long unstructured form.

---

# DARK MODE

Polish dark mode globally.

Verify:

- body
- sidebar
- topbar
- cards
- inputs
- tables
- dropdowns
- modals
- charts
- tooltips
- badges
- empty states
- focus states

No unreadable gray-on-gray combinations.

---

# MOBILE EXPERIENCE — CRITICAL

The mobile UI must feel similar to an Android finance app.

Test:

```text
360 x 800
390 x 844
412 x 915
430px width
```

At mobile breakpoint:

Do NOT display the desktop sidebar.

Use:

```text
Mobile Top App Bar
Scrollable Content
Bottom Navigation
Floating Action Button
```

---

# MOBILE TOP APP BAR

Compact topbar.

May show:

```text
page title
avatar
optional real action
```

Do not use desktop-sized header.

---

# MOBILE BOTTOM NAVIGATION

Create a fixed bottom navigation with max 5 items.

Recommended:

```text
Dasbor
Transaksi
Akun
Laporan
Lainnya
```

Use:

```text
icon + label
```

Active state must be obvious.

Respect:

```css
env(safe-area-inset-bottom)
```

Add enough bottom padding to page content so navigation does not overlap.

---

# MOBILE "LAINNYA" SHEET

`Lainnya` opens a bottom sheet using Alpine.js.

Contains:

```text
Kategori
Transfer
Anggaran
Tagihan
Utang & Piutang
Target Tabungan
Pengaturan
Profil
Keluar
```

Requirements:

- Slide from bottom.
- Backdrop.
- Close button.
- Click outside.
- Scroll lock if practical.
- Touch-friendly rows.

---

# MOBILE FORMS

Mobile forms must:

- Use full width.
- Use comfortable input height.
- Avoid narrow two-column layouts.
- Avoid horizontal scrolling.
- Use correct inputmode.
- Keep primary action easy to reach.

---

# MOBILE TYPOGRAPHY

Recommended:

```text
Body: 14–16px
Label: 13–14px
Page Title: 22–26px
Financial Values: 20–28px
```

Do not make mobile text tiny.

---

# MOBILE TABLE STRATEGY

Do not squeeze desktop tables into mobile.

Transform appropriate tables into stacked rows/cards.

Example transaction:

```text
[Makanan]                 -250.000
Cash
11 Agu 2026
```

---

# MOBILE FILTERS

Complex filters should collapse into a mobile filter sheet.

Show active filter count when useful.

---

# PWA-LIKE VISUAL PREPARATION

Do not implement full PWA or offline service worker in this sprint.

But prepare the UI to feel installable:

- Correct viewport.
- Theme-color metadata if appropriate.
- App-like shell.
- Touch-friendly controls.
- No desktop sidebar on mobile.
- No horizontal overflow.

---

# TOAST NOTIFICATIONS

Create reusable Alpine.js toast notifications.

Types:

```text
success
error
warning
info
```

Examples:

```text
Akun berhasil dibuat
Transaksi berhasil disimpan
Transfer berhasil dilakukan
```

Validation errors should still appear near fields.

---

# CONFIRMATION MODAL

Create one reusable confirmation modal.

Use for:

```text
delete
archive
destructive actions
```

Do not use browser `confirm()` where the reusable modal can safely replace it.

---

# EMPTY STATES

Improve all empty states.

Structure:

```text
icon
title
short description
primary action
```

Example:

```text
Belum ada transaksi

Catat pemasukan atau pengeluaran pertama Anda.

[Tambah Transaksi]
```

No dummy entries.

---

# LOADING / SUBMIT STATES

Prevent duplicate submissions.

On submit:

- Disable button.
- Show spinner or text:

```text
Menyimpan...
```

Do not alter backend behavior.

---

# NUMBER FORMATTING

Display IDR consistently.

Prefer:

```text
Rp 8.747.500
```

or existing:

```text
IDR 8.747.500
```

Choose ONE style and use it everywhere.

Avoid `.00` for IDR display if decimals are not used.

Do not change database precision.

---

# DATE FORMATTING

Use consistent Indonesian-friendly display:

```text
11 Agu 2026
```

Keep internal date format unchanged.

---

# COMPONENT CONSOLIDATION

Extract reusable Blade components only where it clearly reduces duplication.

Possible components:

```text
x-button
x-input
x-select
x-card
x-badge
x-empty-state
x-page-header
x-modal
x-toast
x-money
x-mobile-nav
```

Do not perform a massive backend refactor.

---

# ACCESSIBILITY

Verify:

- Labels exist.
- Icon-only buttons have aria-label.
- Focus states visible.
- Modals are keyboard usable where practical.
- Contrast is sufficient.
- Color is not the only status indicator.

---

# PERFORMANCE

Do not significantly increase page load time.

Avoid:

- Heavy animation libraries.
- Huge image assets.
- Multiple icon libraries.
- New UI frameworks.

Use lightweight Alpine.js and Tailwind transitions.

---

# REQUIRED SCREEN REVIEW

Polish all implemented screens:

```text
Login
Forgot Password
Reset Password
Dashboard
Accounts
Account Create/Edit
Categories
Category Create/Edit
Transactions
Transaction Create/Edit
Transfers
Budgets
Bills
Debt & Receivable
Saving Goals
Reports
Settings
Profile
Attachment/Export UI
```

Do not leave old visual style on secondary pages.

---

# BREAKPOINT REVIEW

Inspect:

```text
1440px
1280px
768px
430px
390px
360px
```

Fix:

- overflow
- clipping
- broken dropdowns
- broken modals
- unusable tables
- excessive whitespace
- tiny tap targets
- bottom-nav overlap

---

# REGRESSION SAFETY

After UI changes, test:

```text
Login
Logout
Create account
Edit account
Create category
Create income
Create expense
Edit transaction
Delete transaction
Create transfer
Edit transfer
Budget
Bill
Debt payment
Receivable payment
Saving goal
Reports
Settings
Profile
Export
Attachment upload
```

Financial outputs must not change.

---

# FINANCIAL REGRESSION TEST

Re-run the validated test data.

Expected:

```text
Bank BCA = 7,497,500
Cash     = 1,250,000
Total    = 8,747,500
Income   = 3,000,000
Expense  = 250,000
Fee      = 2,500
Net Flow = 2,747,500
```

Result must remain identical after UI refactor.

---

# FRONTEND BUILD

Run:

```bash
npm run build
```

Must pass.

Production must remain independent of:

```bash
npm run dev
```

---

# CHANGELOG

Update `CHANGELOG.md`.

Suggested entries:

```md
### Changed
- Redesigned authenticated application shell.
- Improved dashboard cards, charts, forms, tables, and data lists.
- Improved responsive behavior across all screens.
- Standardized money/date presentation.
- Improved dark mode.

### Added
- Collapsible desktop sidebar.
- Mobile Android-style bottom navigation.
- Mobile "Lainnya" bottom sheet.
- Floating quick-add transaction action.
- Reusable toast notifications.
- Reusable confirmation modal.
- Visual icon and color selection.
```

Only list features actually implemented.

---

# DEFINITION OF DONE

Sprint 17 is complete only when:

- [ ] Desktop shell looks modern.
- [ ] Sidebar is polished.
- [ ] Sidebar collapse works if implemented.
- [ ] Topbar is modernized.
- [ ] Dashboard is polished.
- [ ] Account list and forms are polished.
- [ ] Icon picker replaces raw icon text input.
- [ ] Color presets/picker are user-friendly.
- [ ] Transaction workflow is optimized.
- [ ] Transfer UI is clearer.
- [ ] Budgets are polished.
- [ ] Bills are polished.
- [ ] Debt/receivable screens are polished.
- [ ] Saving goals are polished.
- [ ] Reports are polished.
- [ ] Settings are grouped clearly.
- [ ] Dark mode works.
- [ ] Desktop sidebar is hidden on mobile.
- [ ] Mobile top app bar exists.
- [ ] Mobile bottom navigation exists.
- [ ] Mobile Lainnya sheet exists.
- [ ] Mobile FAB exists.
- [ ] Mobile forms feel app-like.
- [ ] Tables adapt on mobile.
- [ ] No horizontal overflow at 360px.
- [ ] Toasts work.
- [ ] Confirmation modal works.
- [ ] Empty states are polished.
- [ ] No dummy data added.
- [ ] No financial logic changed.
- [ ] Financial regression test passes.
- [ ] Authorization still passes.
- [ ] `npm run build` passes.
- [ ] CHANGELOG updated.

---

# STRICTLY FORBIDDEN

Do not:

```text
change financial formulas
change database schema for cosmetic reasons
rebuild backend modules
replace Blade with SPA framework
add dummy data
add fake charts
add fake balances
add unnecessary dependencies
introduce Redis
introduce WebSockets
introduce Node.js production runtime
perform deployment changes
```

---

# FINAL COMPLETION REPORT

When complete, respond using:

```md
# SPRINT 17 COMPLETION REPORT

## Status
COMPLETE / PARTIAL / BLOCKED

## UI Foundation
- Desktop shell:
- Sidebar:
- Topbar:
- Page headers:
- Reusable components:

## Mobile App Experience
- Mobile topbar:
- Bottom navigation:
- Lainnya sheet:
- FAB:
- Mobile forms:
- Mobile tables/lists:

## Screens Polished
- Dashboard:
- Accounts:
- Categories:
- Transactions:
- Transfers:
- Budgets:
- Bills:
- Debts & Receivables:
- Saving Goals:
- Reports:
- Settings:
- Profile:
- Authentication:

## UX Improvements
- Icon picker:
- Color picker:
- Toast:
- Confirmation modal:
- Empty states:
- Loading states:

## Responsive Verification
- 1440px:
- 1280px:
- 768px:
- 430px:
- 390px:
- 360px:

## Dark Mode
PASS / FAIL

## Financial Regression
- Total balance:
- Income:
- Expense:
- Transfer:
- Fee:
- Net flow:
- Result: PASS / FAIL

## Security Regression
- Auth:
- Ownership:
- CSRF:
- Logout:
- Attachment access:
- Result:

## Build
- npm run build:
- Result:

## Files Created
- ...

## Files Modified
- ...

## Files Deleted
- ...

## Packages Added
- ...

## Important Decisions
- ...

## Known Limitations
- ...

## Ready for Final Production Deployment
YES / NO
```

---

# FINAL INSTRUCTION

This sprint is strictly for UI/UX modernization.

Preserve every working financial function.

The desktop experience must feel modern and professional.

The mobile experience must feel close to a native Android personal finance application while remaining a responsive Laravel web application.
