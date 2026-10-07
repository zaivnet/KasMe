# DESIGN SYSTEM

## 1. Design Direction

The application should feel:

- Modern.
- Clean.
- Professional.
- Calm.
- Financially trustworthy.
- Easy to scan.
- Mobile-friendly.

Avoid visual clutter and excessive decorative elements.

---

## 2. Layout Principles

Desktop:

```text
+----------------+-----------------------------------+
| Sidebar        | Topbar                            |
|                +-----------------------------------+
| Navigation     | Main Content                      |
|                |                                   |
|                |                                   |
+----------------+-----------------------------------+
```

Mobile:

```text
+-----------------------------------+
| Mobile Header                     |
+-----------------------------------+
| Main Content                      |
|                                   |
+-----------------------------------+
| Optional Bottom Navigation        |
+-----------------------------------+
```

Sidebar should collapse or transform appropriately on smaller screens.

---

## 3. Visual Hierarchy

Priority:

1. Page title.
2. Primary financial metrics.
3. Main action.
4. Main data table/chart.
5. Secondary filters/details.

Avoid displaying too many equal-weight cards.

---

## 4. Typography

Prefer a clean sans-serif system.

Recommended:

- Inter.
- System UI fallback.

Example:

```css
font-family: Inter, ui-sans-serif, system-ui, sans-serif;
```

Typography levels:

- Page title.
- Section title.
- Card title.
- Body text.
- Helper text.
- Table text.
- Caption.

Do not use excessive font sizes.

---

## 5. Color Roles

Use semantic color roles rather than arbitrary colors.

Suggested roles:

```text
Primary
Success
Danger
Warning
Info
Surface
Background
Border
Text Primary
Text Secondary
Muted
```

Financial semantics:

- Income: success.
- Expense: danger.
- Warning budget: warning.
- Neutral balance: primary or text color.

Colors must remain readable in light and dark themes.

---

## 6. Cards

Cards should use:

- Consistent border radius.
- Subtle border.
- Minimal shadow.
- Consistent padding.

Avoid overly strong shadows.

Financial summary cards may contain:

```text
Label
Main value
Comparison / helper
Optional icon
```

---

## 7. Buttons

Button variants:

- Primary.
- Secondary.
- Danger.
- Ghost.
- Icon.

Rules:

- Use one primary action per logical section.
- Destructive buttons must look destructive.
- Icon-only buttons require accessible labels/tooltips.

---

## 8. Forms

Forms must have:

- Label.
- Input.
- Validation error.
- Optional helper text.

Required fields must be clearly identifiable.

Financial amount input:

- Numeric.
- Appropriate decimal handling.
- Currency context.

Do not rely only on placeholders as labels.

---

## 9. Tables

Financial tables must prioritize readability.

Recommended columns may include:

- Date.
- Account.
- Category.
- Description.
- Amount.
- Status.
- Actions.

Rules:

- Income and expense must be visually distinguishable.
- Amount alignment should be consistent.
- Use pagination for large datasets.
- Mobile may use stacked cards where tables become impractical.

---

## 10. Badges

Badges may represent:

- Paid.
- Unpaid.
- Overdue.
- Active.
- Completed.
- Expense.
- Income.

Badge color must follow semantic meaning.

---

## 11. Empty States

Every major list must have a useful empty state.

Example:

```text
No transactions yet.

Start by adding your first income or expense.
[Add Transaction]
```

Do not populate empty screens with dummy data.

---

## 12. Loading States

For AJAX or dynamic interactions, show:

- Spinner.
- Skeleton.
- Disabled action state.

Do not let users submit critical financial forms multiple times.

---

## 13. Confirmation Dialogs

Use confirmation for destructive or financially significant operations.

Examples:

- Delete transaction.
- Delete account.
- Delete transfer.
- Mark debt paid if irreversible.
- Remove attachment.

Confirmation text must describe the actual consequence.

---

## 14. Dashboard

Preferred dashboard sections:

```text
Page Header

Summary Cards
- Total Balance
- Income
- Expense
- Net Cash Flow

Charts
- Income vs Expense
- Expense by Category

Secondary Widgets
- Accounts
- Budget
- Upcoming Bills

Recent Transactions
```

Dashboard must display real data only.

---

## 15. Charts

Use Chart.js.

Charts must:

- Have readable legends.
- Have meaningful labels.
- Avoid unnecessary 3D effects.
- Avoid excessive chart types.
- Work in dark mode.
- Remain usable on mobile.

Recommended:

- Line chart.
- Bar chart.
- Doughnut chart.

---

## 16. Responsive Rules

Target widths:

- Mobile.
- Tablet.
- Desktop.
- Large desktop.

Mobile priorities:

- Primary values first.
- Filters may collapse.
- Tables may become cards.
- Forms should use full width where practical.
- Tap targets must be sufficiently large (minimum >= 44x44px).
- Fixed-Navigation Clearance: `<main id="main-content">` uses `.mobile-safe` with dynamic calculation:
  `padding-bottom: calc(var(--mobile-nav-height, 4.5rem) + env(safe-area-inset-bottom, 0px) + 2rem);`
  This guarantees all form actions and submit buttons remain 100% visible and scrollable above the bottom navigation bar across all mobile viewports (360x800, 390x844, 412x915).
- Desktop Responsive Reset: On desktop (`@media (min-width: 1024px)`), `.mobile-safe` padding-bottom resets to `2rem` (32px), avoiding redundant whitespace.
- FAB Visibility Rule: Mobile Floating Action Button (`#mobile-fab`) is strictly hidden on create and edit routes (`transactions.create`, `transactions.edit`, `*.create`, `*.edit`, `profile.edit`, `settings.edit`) to prevent collision with submit buttons, input fields, and attachment uploaders. It is visible only on browsing routes (e.g. Dashboard, Transactions index).

---

## 17. Accessibility

Minimum requirements:

- Proper labels.
- Semantic HTML.
- Keyboard-accessible controls.
- Visible focus states.
- Sufficient color contrast (WCAG AA).
- Minimum touch target 44x44px (`min-h-11`, `w-full sm:w-auto` for form buttons).
- Icons must not be the only status signal where clarity matters.

---

## 18. Dark Mode

The system should support:

```text
light
dark
system
```

Dark mode must preserve:

- Contrast.
- Chart readability.
- Form readability.
- Badge semantics.
- Border visibility.

### Semantic Icon Badge Tokens

To avoid washed-out pastel backgrounds or low contrast between icon foreground and badge container in dark mode, badges use high-contrast dark tokens with `dark:bg-none`:

| Role / Semantic | Light Mode Badge | Dark Mode Badge | Dark Icon Foreground |
|---|---|---|---|
| **Kas Berjalan / Setoran (Teal)** | `bg-gradient-to-br from-teal-50 to-teal-100/70 border-teal-200/70 text-teal-700` | `dark:bg-none dark:bg-teal-950/60 dark:border-teal-800/60` | `dark:text-teal-400` |
| **Saldo / Akun** | `bg-gradient-to-br from-blue-50 to-blue-100/70 border-blue-200/70 text-blue-700` | `dark:bg-none dark:bg-blue-950/60 dark:border-blue-800/60` | `dark:text-blue-400` |
| **Pemasukan** | `bg-gradient-to-br from-emerald-50 to-emerald-100/70 border-emerald-200/70 text-emerald-700` | `dark:bg-none dark:bg-emerald-950/60 dark:border-emerald-800/60` | `dark:text-emerald-400` |
| **Pengeluaran** | `bg-gradient-to-br from-rose-50 to-rose-100/70 border-rose-200/70 text-rose-700` | `dark:bg-none dark:bg-rose-950/60 dark:border-rose-800/60` | `dark:text-rose-400` |
| **Net Cash Flow** | `bg-gradient-to-br from-violet-50 to-violet-100/70 border-violet-200/70 text-violet-700` | `dark:bg-none dark:bg-violet-950/60 dark:border-violet-800/60` | `dark:text-violet-400` |
| **Transfer / Cyan** | `bg-gradient-to-br from-cyan-50 to-cyan-100/70 border-cyan-200/70 text-cyan-700` | `dark:bg-none dark:bg-cyan-950/60 dark:border-cyan-800/60` | `dark:text-cyan-400` |
| **Anggaran / Amber** | `bg-gradient-to-br from-amber-50 to-amber-100/70 border-amber-200/70 text-amber-800` | `dark:bg-none dark:bg-amber-950/60 dark:border-amber-800/60` | `dark:text-amber-400` |
| **Netral / Slate** | `bg-gradient-to-br from-slate-100 to-slate-200/70 border-slate-200/80 text-slate-700` | `dark:bg-none dark:bg-slate-800/60 dark:border-slate-700/60` | `dark:text-slate-300` |

---

## 19. Iconography

Use one consistent icon family.

Do not mix many unrelated icon styles.

Possible implementation:

- Heroicons.
- Lucide.

Only one should be used unless explicitly required.

---

## 20. Design Governance

AI must not introduce:

- New color systems.
- New spacing systems.
- New typography systems.
- New component libraries.

without explicit approval.

Reusable UI should be implemented through shared Blade components whenever practical.
