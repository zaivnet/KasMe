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
- Tap targets must be sufficiently large.

---

## 17. Accessibility

Minimum requirements:

- Proper labels.
- Semantic HTML.
- Keyboard-accessible controls.
- Visible focus states.
- Sufficient color contrast.
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
